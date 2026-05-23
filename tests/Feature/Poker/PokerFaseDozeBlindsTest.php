<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerFaseDozeBlindsTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_seat_inicia_mao_com_dealer_small_blind_big_blind_e_primeiro_pre_flop(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa fase 12.7 blinds',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(4)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.phase', '12.7')
            ->assertJsonPath('state.multiSeat.blinds.phase', '12.7')
            ->assertJsonPath('state.multiSeat.dealerSeat', 1)
            ->assertJsonPath('state.multiSeat.smallBlindSeat', 2)
            ->assertJsonPath('state.multiSeat.bigBlindSeat', 3)
            ->assertJsonPath('state.multiSeat.firstPreFlopSeat', 4)
            ->assertJsonPath('state.multiSeat.firstPostFlopSeat', 2)
            ->assertJsonPath('state.currentTurn.seatNumber', 4)
            ->assertJsonPath('state.pot', 30)
            ->assertJsonPath('state.multiSeat.players.0.isDealer', true)
            ->assertJsonPath('state.multiSeat.players.1.isSmallBlind', true)
            ->assertJsonPath('state.multiSeat.players.1.streetBet', 10)
            ->assertJsonPath('state.multiSeat.players.2.isBigBlind', true)
            ->assertJsonPath('state.multiSeat.players.2.streetBet', 20);
    }

    public function test_dealer_button_gira_na_proxima_mao_multi_seat(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa fase 12.7 dealer gira',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(4)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.dealerSeat', 1);

        PokerHand::query()
            ->where('poker_table_id', $table->id)
            ->latest('id')
            ->firstOrFail()
            ->forceFill([
                'status' => 'finished',
                'finished_at' => now(),
            ])
            ->save();

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.dealerSeat', 2)
            ->assertJsonPath('state.multiSeat.smallBlindSeat', 3)
            ->assertJsonPath('state.multiSeat.bigBlindSeat', 4)
            ->assertJsonPath('state.multiSeat.firstPreFlopSeat', 1)
            ->assertJsonPath('state.multiSeat.firstPostFlopSeat', 3)
            ->assertJsonPath('state.currentTurn.seatNumber', 1);
    }

    public function test_blind_maior_que_stack_coloca_jogador_em_all_in_sem_stack_negativo(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa fase 12.7 blind all-in',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 3,
        ]);
        $users = User::factory()->count(3)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $table->realPlayers()->where('seat_number', 3)->firstOrFail()->forceFill(['stack' => 5])->save();

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.bigBlindSeat', 3)
            ->assertJsonPath('state.multiSeat.players.2.streetBet', 5)
            ->assertJsonPath('state.multiSeat.players.2.stack', 0)
            ->assertJsonPath('state.multiSeat.players.2.status', 'all_in')
            ->assertJsonPath('state.pot', 15);
    }

    public function test_jogador_sem_stack_nao_reentra_automaticamente_na_nova_mao_multi_seat(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa fase 13 hotfix sem rebuy automatico',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(4)->create();

        foreach ($users as $index => $user) {
            PokerTablePlayer::query()->create([
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
                'nickname' => 'Jogador teste '.($index + 1),
                'stack' => $index < 2 ? 0 : 3000,
                'buy_in_amount' => 1000,
                'buy_in_paid_at' => now(),
                'seat_number' => $index + 1,
                'status' => 'online',
                'is_bot' => false,
                'joined_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        $response = $this->actingAs($users[2])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true);

        $players = collect($response->json('state.multiSeat.players'));

        $this->assertSame([3, 4], $players->pluck('seatNumber')->all());
        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $users[0]->id,
            'stack' => 0,
            'seat_number' => 1,
        ]);
        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $users[1]->id,
            'stack' => 0,
            'seat_number' => 2,
        ]);
    }

    public function test_mesa_multi_seat_aguarda_rebuy_manual_quando_resta_apenas_um_jogador_com_fichas(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa fase 13 hotfix aguarda rebuy manual',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

        foreach ($users as $index => $user) {
            PokerTablePlayer::query()->create([
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
                'nickname' => 'Jogador teste '.($index + 1),
                'stack' => $index === 0 ? 2500 : 0,
                'buy_in_amount' => 1000,
                'buy_in_paid_at' => now(),
                'seat_number' => $index + 1,
                'status' => 'online',
                'is_bot' => false,
                'joined_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true)
            ->assertJsonPath('state.waitingForPlayers.playersSeated', 1)
            ->assertJsonPath('state.waitingForPlayers.playersNeeded', 1)
            ->assertJsonPath('message', 'A mesa precisa de mais jogador sentado para iniciar a mão.');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $users[1]->id,
            'stack' => 0,
            'seat_number' => 2,
        ]);
    }


}
