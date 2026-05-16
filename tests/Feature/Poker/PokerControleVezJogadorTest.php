<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Application\Poker\StartPokerHandAction;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerControleVezJogadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloqueia_jogador_que_tenta_agir_fora_da_vez(): void
    {
        [$table, $userOne, $userTwo] = $this->createTableWithTwoRealPlayers();

        $this->actingAs($userTwo)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Ainda não é a sua vez de agir nesta mesa.');
    }

    public function test_jogador_da_vez_consegue_agir_e_passa_a_vez_para_o_adversario(): void
    {
        [$table, $userOne, $userTwo] = $this->createTableWithTwoRealPlayers();

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.currentTurn.canonicalActor', 'opponent')
            ->assertJsonPath('state.currentTurn.actorLabel', 'Jogador Y')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', false)
            ->assertJsonPath('state.canAct', false);

        $this->actingAs($userTwo)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.currentTurn.canonicalActor', 'opponent')
            ->assertJsonPath('state.currentTurn.actorLabel', 'Você')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', true)
            ->assertJsonPath('state.canAct', true);
    }


    public function test_jogador_precisa_escolher_assento_antes_de_agir(): void
    {
        $user = User::factory()->create(['name' => 'Jogador sem assento']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa exige assento',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        app(LocalPokerPersistenceService::class)->startOnTable(
            $table,
            app(StartPokerHandAction::class)->execute(),
        );

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Você precisa escolher um assento antes de agir nesta mesa.');
    }

    public function test_jogador_precisa_estar_na_mesa_para_agir(): void
    {
        [$table] = $this->createTableWithTwoRealPlayers();
        $outsider = User::factory()->create(['name' => 'Visitante']);

        $this->actingAs($outsider)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Você precisa entrar na mesa antes de agir.');
    }


    public function test_apos_raise_do_adversario_e_call_do_jogador_a_proxima_street_volta_para_quem_aumentou(): void
    {
        [$table, $userOne, $userTwo] = $this->createTableWithTwoRealPlayers();

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.currentTurn.canonicalActor', 'opponent');

        $this->actingAs($userTwo)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'raise',
                'raiseAmount' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('state.currentTurn.canonicalActor', 'player');

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.currentTurn.canonicalActor', 'opponent')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', false)
            ->assertJsonPath('state.canAct', false);

        $this->actingAs($userTwo)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.currentTurn.canonicalActor', 'opponent')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', true)
            ->assertJsonPath('state.canAct', true)
            ->assertJsonPath('state.canCheck', true);
    }

    /**
     * @return array{0: PokerTable, 1: User, 2: User}
     */
    private function createTableWithTwoRealPlayers(): array
    {
        $userOne = User::factory()->create(['name' => 'Jogador X']);
        $userTwo = User::factory()->create(['name' => 'Jogador Y']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa controle de vez',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $userOne->id,
            'nickname' => $userOne->name,
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $userTwo->id,
            'nickname' => $userTwo->name,
            'stack' => 1000,
            'seat_number' => 2,
            'status' => 'online',
            'joined_at' => now()->addSecond(),
        ]);

        $canonicalPlayer = $table->players()->create([
            'seat' => 1,
            'name' => 'Jogador X',
            'type' => 'real_user',
            'stack' => 990,
        ]);

        $canonicalOpponent = $table->players()->create([
            'seat' => 2,
            'name' => 'Jogador Y',
            'type' => 'real_user',
            'stack' => 980,
        ]);

        $playerSeat = $table->seats()->create([
            'poker_player_id' => $canonicalPlayer->id,
            'seat_number' => 1,
            'status' => 'occupied',
            'role' => 'real_user',
            'stack_snapshot' => 990,
            'is_dealer' => true,
            'is_small_blind' => true,
            'is_big_blind' => false,
        ]);

        $opponentSeat = $table->seats()->create([
            'poker_player_id' => $canonicalOpponent->id,
            'seat_number' => 2,
            'status' => 'occupied',
            'role' => 'real_user',
            'stack_snapshot' => 980,
            'is_dealer' => false,
            'is_small_blind' => false,
            'is_big_blind' => true,
        ]);

        $hand = $table->hands()->create([
            'code' => (string) Str::uuid(),
            'status' => 'running',
            'street' => 'pre_flop',
            'pot' => 30,
            'current_bet' => 20,
            'dealer_position' => 1,
            'state_payload' => [],
            'started_at' => now(),
        ]);

        $hand->forceFill([
            'state_payload' => [
                'street' => 'pre_flop',
                'streetLabel' => 'Pré-flop',
                'pot' => 30,
                'playerStack' => 990,
                'opponentStack' => 980,
                'currentBet' => 20,
                'playerStreetBet' => 10,
                'opponentStreetBet' => 20,
                'amountToCall' => 10,
                'minimumRaise' => 20,
                'minimumRaiseTo' => 40,
                'maximumRaiseTo' => 1000,
                'smallBlind' => 10,
                'bigBlind' => 20,
                'dealerPosition' => 1,
                'canCheck' => false,
                'canCall' => true,
                'canRaise' => true,
                'isFinished' => false,
                'playerCards' => [
                    ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                    ['rank' => 'K', 'suit' => 'spades', 'label' => 'K♠'],
                ],
                'opponentCards' => [
                    ['rank' => '7', 'suit' => 'diamonds', 'label' => '7♦'],
                    ['rank' => '7', 'suit' => 'clubs', 'label' => '7♣'],
                ],
                'communityCards' => [],
                'bestHand' => ['name' => 'Carta alta', 'rank' => 1, 'kickers' => [], 'cards' => []],
                'opponentBestHand' => ['name' => 'Par', 'rank' => 2, 'kickers' => [], 'cards' => []],
                'actionHistory' => [],
                'conclusion' => null,
                'currentTurn' => [
                    'actor' => 'player',
                    'actedThisStreet' => [
                        'player' => false,
                        'opponent' => false,
                    ],
                    'label' => 'Vez do jogador',
                ],
                'persistence' => [
                    'tableId' => $table->id,
                    'handId' => $hand->id,
                    'playerId' => $canonicalPlayer->id,
                    'opponentId' => $canonicalOpponent->id,
                    'playerSeatId' => $playerSeat->id,
                    'opponentSeatId' => $opponentSeat->id,
                    'loggedActions' => 0,
                    'syncVersion' => 1,
                ],
            ],
        ])->save();

        return [$table, $userOne, $userTwo];
    }
}
