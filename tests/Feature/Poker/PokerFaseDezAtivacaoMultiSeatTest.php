<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerFaseDezAtivacaoMultiSeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_mesa_tres_mais_inicia_mao_multi_seat_controlada_com_tres_jogadores_sentados(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat ligada',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

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

        $this->actingAs($users->first())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('multiSeatEnabled', true)
            ->assertJsonPath('engineMode', 'multi_seat')
            ->assertJsonPath('stateContracts.active', 'multi_seat')
            ->assertJsonPath('stateContracts.compatibility.multiSeatEnabled', true)
            ->assertJsonPath('stateContracts.multiSeat.activation.phase', '10.9')
            ->assertJsonPath('state.multiSeat.enabled', true)
            ->assertJsonPath('state.multiSeat.players.2.seatNumber', 3)
            ->assertJsonPath('state.currentTurn.seatNumber', 1)
            ->assertJsonPath('state.canAct', true);

        $this->assertSame(1, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }

    public function test_acao_multi_seat_persiste_sem_erro_de_mao_ativa(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat com ação',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

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

        $this->actingAs($users->first())
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raise_amount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true)
            ->assertJsonPath('state.currentTurn.seatNumber', 2)
            ->assertJsonPath('state.canAct', false);
    }

    public function test_rodada_multi_seat_avanca_street_quando_todos_igualam_aposta(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat fecha rodada',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

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
        ])->assertOk()->assertJsonPath('state.currentTurn.seatNumber', 2);

        $this->actingAs($users[1])->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
            'raise_amount' => 0,
        ])->assertOk()->assertJsonPath('state.currentTurn.seatNumber', 3);

        $this->actingAs($users[2])->postJson(route('poker.tables.actions', $table), [
            'action' => 'check',
            'raise_amount' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true)
            ->assertJsonPath('state.multiSeat.lastStreetClosed', true)
            ->assertJsonPath('state.multiSeat.streetClosurePhase', '10.10')
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.currentBet', 0)
            ->assertJsonPath('state.currentTurn.seatNumber', 1)
            ->assertJsonPath('state.multiSeat.players.0.streetBet', 0)
            ->assertJsonPath('state.multiSeat.players.1.streetBet', 0)
            ->assertJsonPath('state.multiSeat.players.2.streetBet', 0);
    }


    public function test_showdown_multi_seat_controlado_finaliza_apos_river_resolvido(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat showdown controlado',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

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
            'action' => 'call',
            'raise_amount' => 0,
        ])->assertOk();
        $this->actingAs($users[2])->postJson(route('poker.tables.actions', $table), [
            'action' => 'check',
            'raise_amount' => 0,
        ])->assertOk()->assertJsonPath('state.street', 'flop');

        foreach (['turn', 'river', 'showdown'] as $expectedStreet) {
            $this->actingAs($users[0])->postJson(route('poker.tables.actions', $table), [
                'action' => 'check',
                'raise_amount' => 0,
            ])->assertOk();
            $this->actingAs($users[1])->postJson(route('poker.tables.actions', $table), [
                'action' => 'check',
                'raise_amount' => 0,
            ])->assertOk();
            $response = $this->actingAs($users[2])->postJson(route('poker.tables.actions', $table), [
                'action' => 'check',
                'raise_amount' => 0,
            ])->assertOk()->assertJsonPath('state.street', $expectedStreet);
        }

        $response
            ->assertJsonPath('state.isFinished', true)
            ->assertJsonPath('state.currentTurn.seatNumber', null)
            ->assertJsonPath('state.multiSeat.showdownResolutionPhase', '10.12')
            ->assertJsonPath('state.multiSeat.showdownEvaluator', 'real_hand_evaluator');

        $payload = $response->json('state');
        $winnerSeats = $payload['multiSeat']['winnerSeats'] ?? [];

        $this->assertNotEmpty($winnerSeats);
        $this->assertContains($payload['conclusion']['winner']['seatNumber'] ?? null, $winnerSeats);
        $this->assertNotSame('Showdown multi-seat', $payload['conclusion']['winner']['handName'] ?? null);
    }


    public function test_aposta_multi_seat_respeita_all_in_curto_sem_devolver_turno_ao_jogador_zerado(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat all-in curto',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

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

        $hand = PokerHand::query()->where('poker_table_id', $table->id)->latest('id')->firstOrFail();
        $state = $hand->state_payload;
        $state['pot'] = 200;
        $state['currentBet'] = 100;
        $state['minimumRaise'] = 20;
        $state['minimumRaiseTo'] = 120;
        $state['amountToCall'] = 100;
        $state['multiSeat']['currentSeat'] = 1;
        $state['currentTurn']['seatNumber'] = 1;
        $state['currentTurn']['actor'] = 'seat:1';
        $state['multiSeat']['players'][0]['stack'] = 50;
        $state['multiSeat']['players'][0]['streetBet'] = 0;
        $state['multiSeat']['players'][0]['hasActed'] = false;
        $state['multiSeat']['players'][1]['stack'] = 900;
        $state['multiSeat']['players'][1]['streetBet'] = 100;
        $state['multiSeat']['players'][1]['hasActed'] = true;
        $state['multiSeat']['players'][2]['stack'] = 900;
        $state['multiSeat']['players'][2]['streetBet'] = 100;
        $state['multiSeat']['players'][2]['hasActed'] = true;
        $hand->forceFill([
            'pot' => 200,
            'current_bet' => 100,
            'state_payload' => $state,
        ])->save();

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raise_amount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.multiSeat.bettingEnginePhase', '10.13')
            ->assertJsonPath('state.multiSeat.lastBettingAction.isAllIn', true)
            ->assertJsonPath('state.multiSeat.lastBettingAction.amountCommitted', 50)
            ->assertJsonPath('state.multiSeat.players.0.stack', 0)
            ->assertJsonPath('state.multiSeat.players.0.isAllIn', true)
            ->assertJsonPath('state.multiSeat.lastStreetClosed', true)
            ->assertJsonPath('state.currentTurn.seatNumber', 2);
    }



    public function test_timeout_multi_seat_nao_usa_motor_heads_up_e_mantem_roleta_no_proximo_assento(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat timeout humano',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

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
        ])
            ->assertOk()
            ->assertJsonPath('state.currentTurn.seatNumber', 2)
            ->assertJsonPath('state.turnTimer.secondsTotal', 30);

        $hand = PokerHand::query()->where('poker_table_id', $table->id)->latest('id')->firstOrFail();
        $state = $hand->state_payload;
        $state['turnTimer']['expiresAt'] = now()->subSecond()->toIso8601String();
        $hand->forceFill(['state_payload' => $state])->save();

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.timeout', $table))
            ->assertOk()
            ->assertJsonPath('processed', true)
            ->assertJsonPath('action', 'fold')
            ->assertJsonPath('state.multiSeat.enabled', true)
            ->assertJsonPath('state.turnTimeout.engine', 'multi_seat')
            ->assertJsonPath('state.turnTimeout.seatNumber', 2)
            ->assertJsonPath('state.multiSeat.players.1.hasFolded', true)
            ->assertJsonPath('state.currentTurn.seatNumber', 3)
            ->assertJsonPath('state.currentTurn.canonicalActor', 'seat:3')
            ->assertJsonPath('state.currentTurn.actor', 'opponent')
            ->assertJsonPath('state.isFinished', false);
    }

    public function test_timeout_de_bot_multi_seat_processa_assento_atual_sem_pular_para_showdown(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat timeout bot',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

        $this->criarJogadorSentado($table, $users[0], 1, false, 'Adriano');
        $this->criarJogadorSentado($table, $users[1], 2, true, 'Bot TAG');
        $this->criarJogadorSentado($table, $users[2], 3, false, 'Maria');

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true)
            ->assertJsonPath('state.currentTurn.seatNumber', 1)
            ->assertJsonPath('state.turnTimer.secondsTotal', 30);

        $this->actingAs($users[0])->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
            'raise_amount' => 0,
        ])->assertOk()->assertJsonPath('state.currentTurn.seatNumber', 2);

        $hand = PokerHand::query()->where('poker_table_id', $table->id)->latest('id')->firstOrFail();
        $state = $hand->state_payload;
        $state['turnTimer']['expiresAt'] = now()->subSecond()->toIso8601String();
        $hand->forceFill(['state_payload' => $state])->save();

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.timeout', $table))
            ->assertOk()
            ->assertJsonPath('processed', true)
            ->assertJsonPath('action', 'bot')
            ->assertJsonPath('state.turnTimeout.engine', 'multi_seat')
            ->assertJsonPath('state.turnTimeout.seatNumber', 2)
            ->assertJsonPath('state.lastAction.seatNumber', 2)
            ->assertJsonPath('state.lastAction.isBot', true)
            ->assertJsonPath('state.currentTurn.seatNumber', 3)
            ->assertJsonPath('state.turnTimer.secondsTotal', 30)
            ->assertJsonPath('state.isFinished', false);
    }

    public function test_bot_multi_seat_age_sem_esperar_expirar_timer_quando_for_sua_vez(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat bot imediato',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

        $this->criarJogadorSentado($table, $users[0], 1, false, 'Adriano');
        $this->criarJogadorSentado($table, $users[1], 2, true, 'Bot Conservador');
        $this->criarJogadorSentado($table, $users[2], 3, false, 'Maria');

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.currentTurn.seatNumber', 1);

        $this->actingAs($users[0])->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
            'raise_amount' => 0,
        ])->assertOk()->assertJsonPath('state.currentTurn.seatNumber', 2);

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.timeout', $table))
            ->assertOk()
            ->assertJsonPath('processed', true)
            ->assertJsonPath('action', 'bot')
            ->assertJsonPath('state.turnTimeout.engine', 'multi_seat')
            ->assertJsonPath('state.turnTimeout.seatNumber', 2)
            ->assertJsonPath('state.lastAction.isBot', true)
            ->assertJsonPath('state.currentTurn.seatNumber', 3)
            ->assertJsonPath('state.isFinished', false);
    }


    public function test_refresh_da_mesa_multi_seat_nao_inicia_mao_automaticamente(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat sem auto start no refresh',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
        $users = User::factory()->count(3)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $this->actingAs($users[0])
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hand.isWaitingForPlayers', true)
                ->where('hand.turnTimer', null));

        $this->actingAs($users[0])
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true)
            ->assertJsonPath('state.turnTimer', null);

        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }

    public function test_mesa_multi_seat_nao_inicia_mao_antes_de_acionar_nova_mao_para_permitir_mais_assentos(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat aguardando jogadores restantes',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);
        $users = User::factory()->count(4)->create();

        foreach ([0, 1] as $index) {
            $this->actingAs($users[$index])->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($users[$index])->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk()
                ->assertJsonPath('state.isWaitingForPlayers', true);
        }

        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());

        $this->actingAs($users[2])->postJson(route('poker.tables.join', $table))->assertOk();
        $this->actingAs($users[2])->postJson(route('poker.tables.seat', $table), [
            'seat_number' => 3,
        ])->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true);

        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true);

        $this->actingAs($users[3])->postJson(route('poker.tables.join', $table))->assertOk();
        $this->actingAs($users[3])->postJson(route('poker.tables.seat', $table), [
            'seat_number' => 4,
        ])->assertOk();

        $this->assertSame(1, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }

    private function criarJogadorSentado(PokerTable $table, User $user, int $seatNumber, bool $isBot, string $nickname): void
    {
        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $nickname,
            'stack' => 1000,
            'seat_number' => $seatNumber,
            'status' => 'online',
            'is_bot' => $isBot,
            'bot_profile' => $isBot ? 'conservative' : null,
            'bot_difficulty' => $isBot ? 'normal' : null,
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

}
