<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerBankrollFaseOnzeTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_novo_recebe_bankroll_inicial_para_jogar_poker(): void
    {
        $user = User::factory()->create();

        $this->assertSame(10000, $user->fresh()->poker_bankroll);
    }

    public function test_jogador_paga_buy_in_ao_sentar_na_mesa(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);
        $table = $this->createTable();

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), ['seat_number' => 1])
            ->assertOk()
            ->assertJsonPath('player.stack', 1000)
            ->assertJsonPath('player.buyInAmount', 1000)
            ->assertJsonPath('player.seatNumber', 1);

        $this->assertSame(9000, $user->fresh()->poker_bankroll);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'stack' => 1000,
            'buy_in_amount' => 1000,
            'seat_number' => 1,
        ]);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $user->id,
            'poker_table_id' => $table->id,
            'type' => PokerBankrollTransaction::TYPE_BUY_IN,
            'amount' => -1000,
            'balance_before' => 10000,
            'balance_after' => 9000,
        ]);
    }

    public function test_trocar_de_assento_nao_debita_buy_in_novamente(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
        $this->actingAs($user)->postJson(route('poker.tables.seat', $table), ['seat_number' => 1])->assertOk();
        $this->actingAs($user)->postJson(route('poker.tables.seat', $table), ['seat_number' => 2])->assertOk();

        $this->assertSame(9000, $user->fresh()->poker_bankroll);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'buy_in_amount' => 1000,
            'seat_number' => 2,
        ]);

        $this->assertSame(1, PokerBankrollTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', PokerBankrollTransaction::TYPE_BUY_IN)
            ->count());
    }

    public function test_jogador_sem_saldo_suficiente_nao_consegue_sentarse(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 500]);
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), ['seat_number' => 1])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Saldo insuficiente para sentar nesta mesa. Buy-in necessário: 1000 fichas.');

        $this->assertSame(500, $user->fresh()->poker_bankroll);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'seat_number' => null,
            'buy_in_amount' => null,
        ]);
    }

    public function test_tela_da_mesa_informa_bankroll_e_buy_in_padrao(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 7500]);
        $table = $this->createTable();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.currentUserBankroll', 7500)
                ->where('table.defaultBuyIn', 1000));
    }


    public function test_vencedor_multi_seat_recebe_pote_no_bankroll_ao_finalizar_mao(): void
    {
        $users = User::factory()->count(3)->create(['poker_bankroll' => 10000]);
        $table = $this->createTable();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true);

        $this->actingAs($users[0])->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
            'raise_amount' => 0,
        ])->assertOk();

        $this->actingAs($users[1])->postJson(route('poker.tables.actions', $table), [
            'action' => 'fold',
            'raise_amount' => 0,
        ])->assertOk();

        $response = $this->actingAs($users[2])->postJson(route('poker.tables.actions', $table), [
            'action' => 'fold',
            'raise_amount' => 0,
        ])->assertOk()
            ->assertJsonPath('state.isFinished', true)
            ->assertJsonPath('state.bankrollSettlement.applied', true)
            ->assertJsonPath('state.bankrollSettlement.phase', '11.3');

        $payouts = $response->json('state.bankrollSettlement.payoutsBySeat');
        $payoutSeatOne = (int) ($payouts[1] ?? $payouts['1'] ?? 0);

        $this->assertGreaterThan(0, $payoutSeatOne);
        $this->assertSame(9000 + $payoutSeatOne, $users[0]->fresh()->poker_bankroll);
        $this->assertSame(9000, $users[1]->fresh()->poker_bankroll);
        $this->assertSame(9000, $users[2]->fresh()->poker_bankroll);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $users[0]->id,
            'poker_table_id' => $table->id,
            'type' => PokerBankrollTransaction::TYPE_PAYOUT,
            'amount' => $payoutSeatOne,
            'balance_before' => 9000,
            'balance_after' => 9000 + $payoutSeatOne,
        ]);
    }

    public function test_empate_multi_seat_divide_pote_no_bankroll_uma_unica_vez(): void
    {
        $users = User::factory()->count(3)->create(['poker_bankroll' => 10000]);
        $table = $this->createTable();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true);

        /** @var \App\Services\Poker\LocalPokerPersistenceService $persistence */
        $persistence = app(\App\Services\Poker\LocalPokerPersistenceService::class);
        $state = $persistence->currentStateForTable($table);
        $this->assertIsArray($state);

        $state['isFinished'] = true;
        $state['street'] = 'showdown';
        $state['pot'] = 101;
        $state['multiSeat']['winnerSeats'] = [1, 2];
        $state['conclusion'] = [
            'isFinished' => true,
            'winner' => [
                'player' => 'seat:1',
                'seatNumber' => 1,
                'label' => 'Jogador 1',
                'handName' => 'Empate técnico',
            ],
            'message' => 'Pote dividido.',
        ];

        $firstPersist = $persistence->persist($state);
        $secondPersist = $persistence->persist($firstPersist);

        $this->assertTrue($secondPersist['bankrollSettlement']['applied']);
        $this->assertSame(51, (int) $secondPersist['bankrollSettlement']['payoutsBySeat'][1]);
        $this->assertSame(50, (int) $secondPersist['bankrollSettlement']['payoutsBySeat'][2]);
        $this->assertSame(9051, $users[0]->fresh()->poker_bankroll);
        $this->assertSame(9050, $users[1]->fresh()->poker_bankroll);
        $this->assertSame(9000, $users[2]->fresh()->poker_bankroll);

        $this->assertSame(5, PokerBankrollTransaction::query()->where('poker_table_id', $table->id)->count());
        $this->assertSame(2, PokerBankrollTransaction::query()
            ->where('poker_table_id', $table->id)
            ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
            ->count());

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $users[0]->id,
            'poker_table_id' => $table->id,
            'type' => PokerBankrollTransaction::TYPE_PAYOUT,
            'amount' => 51,
            'balance_before' => 9000,
            'balance_after' => 9051,
        ]);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $users[1]->id,
            'poker_table_id' => $table->id,
            'type' => PokerBankrollTransaction::TYPE_PAYOUT,
            'amount' => 50,
            'balance_before' => 9000,
            'balance_after' => 9050,
        ]);
    }


    public function test_mesa_com_buy_in_customizado_debita_o_valor_configurado(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);
        $table = PokerTable::query()->create([
            'name' => 'Mesa Buy-in Alto',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'buy_in' => 2500,
            'max_players' => 4,
        ]);

        $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), ['seat_number' => 1])
            ->assertOk()
            ->assertJsonPath('player.stack', 2500)
            ->assertJsonPath('player.buyInAmount', 2500);

        $this->assertSame(7500, $user->fresh()->poker_bankroll);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $user->id,
            'poker_table_id' => $table->id,
            'type' => PokerBankrollTransaction::TYPE_BUY_IN,
            'amount' => -2500,
            'balance_before' => 10000,
            'balance_after' => 7500,
        ]);
    }

    public function test_criacao_de_mesa_aceita_buy_in_customizado_e_expoe_no_lobby(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);

        $this->actingAs($user)->post(route('poker.tables.store'), [
            'name' => 'Mesa Buy-in Customizado',
            'max_players' => 4,
            'buy_in' => 3200,
        ])->assertRedirect();

        $table = PokerTable::query()->where('name', 'Mesa Buy-in Customizado')->firstOrFail();

        $this->assertSame(3200, $table->buyInAmount());

        $this->actingAs($user)
            ->get(route('poker.lobby'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tables.0.buyIn', 3200)
                ->where('tableCreation.defaultBuyIn', 1000)
                ->where('tableCreation.minBuyIn', 200)
                ->where('tableCreation.maxBuyIn', 10000));
    }


    public function test_criacao_de_mesa_rejeita_buy_in_fora_dos_limites_configurados(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);

        $this->actingAs($user)
            ->from(route('poker.lobby'))
            ->post(route('poker.tables.store'), [
                'name' => 'Mesa Buy-in Inválido',
                'max_players' => 4,
                'buy_in' => 10100,
            ])
            ->assertRedirect(route('poker.lobby'))
            ->assertSessionHasErrors('buy_in');

        $this->assertDatabaseMissing('poker_tables', [
            'name' => 'Mesa Buy-in Inválido',
        ]);
    }

    public function test_criacao_de_mesa_rejeita_buy_in_que_nao_seja_multiplo_do_passo(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);

        $this->actingAs($user)
            ->from(route('poker.lobby'))
            ->post(route('poker.tables.store'), [
                'name' => 'Mesa Buy-in Quebrado',
                'max_players' => 4,
                'buy_in' => 1250,
            ])
            ->assertRedirect(route('poker.lobby'))
            ->assertSessionHasErrors('buy_in');

        $this->assertDatabaseMissing('poker_tables', [
            'name' => 'Mesa Buy-in Quebrado',
        ]);
    }

    public function test_lobby_expoe_opcoes_guiadas_para_buy_in(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);

        $this->actingAs($user)
            ->get(route('poker.lobby'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tableCreation.buyInStep', 100)
                ->where('tableCreation.buyInOptions.0', 500)
                ->where('tableCreation.buyInOptions.4', 10000));
    }

    private function createTable(): PokerTable
    {
        return PokerTable::query()->create([
            'name' => 'Mesa Bankroll',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
    }
}
