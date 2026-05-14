<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerCartasPrivadasPorJogadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_jogador_autenticado_recebe_suas_proprias_cartas_privadas_na_mesa(): void
    {
        $userOne = User::factory()->create(['name' => 'Jogador X']);
        $userTwo = User::factory()->create(['name' => 'Jogador Y']);

        $table = $this->createTableWithRunningHand();

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

        $this->actingAs($userOne)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.playerCards.0.label', 'A♠')
            ->assertJsonPath('state.playerCards.1.label', 'K♠')
            ->assertJsonMissingPath('state.opponentCards')
            ->assertJsonPath('state.multiplayerPerspective.role', 'player')
            ->assertJsonPath('state.playersContext.current.nickname', 'Jogador X')
            ->assertJsonPath('state.playersContext.opponents.0.nickname', 'Jogador Y');

        $this->actingAs($userTwo)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.playerCards.0.label', '7♦')
            ->assertJsonPath('state.playerCards.1.label', '7♣')
            ->assertJsonMissingPath('state.opponentCards')
            ->assertJsonPath('state.multiplayerPerspective.role', 'opponent')
            ->assertJsonPath('state.playersContext.current.nickname', 'Jogador Y')
            ->assertJsonPath('state.playersContext.opponents.0.nickname', 'Jogador X');
    }

    public function test_cartas_do_adversario_sao_exibidas_somente_quando_a_mao_termina(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        $table = $this->createTableWithRunningHand(finished: true);

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

        $this->actingAs($userTwo)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.playerCards.0.label', '7♦')
            ->assertJsonPath('state.opponentCards.0.label', 'A♠')
            ->assertJsonPath('state.multiplayerPerspective.role', 'opponent');
    }


    public function test_resultado_e_historico_sao_personalizados_para_cada_jogador_real(): void
    {
        $userOne = User::factory()->create(['name' => 'Jogador X']);
        $userTwo = User::factory()->create(['name' => 'Jogador Y']);

        $table = $this->createTableWithRunningHand(finished: true);

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

        $this->actingAs($userOne)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.conclusion.winner.player', 'opponent')
            ->assertJsonPath('state.conclusion.winner.label', 'Jogador Y')
            ->assertJsonPath('state.actionHistory.0.actorLabel', 'Você')
            ->assertJsonPath('state.actionHistory.1.actorLabel', 'Jogador Y');

        $this->actingAs($userTwo)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.conclusion.winner.player', 'player')
            ->assertJsonPath('state.conclusion.winner.label', 'Você')
            ->assertJsonPath('state.actionHistory.0.actorLabel', 'Jogador X')
            ->assertJsonPath('state.actionHistory.1.actorLabel', 'Você');
    }

    private function createTableWithRunningHand(bool $finished = false): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa cartas privadas',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $table->hands()->create([
            'code' => (string) Str::uuid(),
            'status' => 'running',
            'street' => $finished ? 'showdown' : 'pre_flop',
            'pot' => 30,
            'current_bet' => 20,
            'dealer_position' => 1,
            'state_payload' => [
                'street' => $finished ? 'showdown' : 'pre_flop',
                'streetLabel' => $finished ? 'Showdown' : 'Pré-flop',
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
                'canCheck' => false,
                'canCall' => true,
                'canRaise' => true,
                'isFinished' => $finished,
                'playerCards' => [
                    ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                    ['rank' => 'K', 'suit' => 'spades', 'label' => 'K♠'],
                ],
                'opponentCards' => [
                    ['rank' => '7', 'suit' => 'diamonds', 'label' => '7♦'],
                    ['rank' => '7', 'suit' => 'clubs', 'label' => '7♣'],
                ],
                'communityCards' => [
                    ['rank' => '2', 'suit' => 'clubs', 'label' => '2♣'],
                    ['rank' => '9', 'suit' => 'hearts', 'label' => '9♥'],
                    ['rank' => 'K', 'suit' => 'clubs', 'label' => 'K♣'],
                    ['rank' => '3', 'suit' => 'spades', 'label' => '3♠'],
                    ['rank' => 'Q', 'suit' => 'diamonds', 'label' => 'Q♦'],
                ],
                'bestHand' => ['name' => 'Par', 'rank' => 2, 'kickers' => [], 'cards' => []],
                'opponentBestHand' => ['name' => 'Trinca', 'rank' => 4, 'kickers' => [], 'cards' => []],
                'actionHistory' => [
                    [
                        'street' => 'Pré-flop',
                        'action' => 'Call',
                        'message' => 'Você pagou a aposta.',
                        'pot' => 40,
                        'actor' => 'player',
                    ],
                    [
                        'street' => 'Pré-flop',
                        'action' => 'Call',
                        'message' => 'Oponente pagou a aposta.',
                        'pot' => 60,
                        'actor' => 'opponent',
                    ],
                ],
                'conclusion' => $finished ? [
                    'isFinished' => true,
                    'reason' => 'showdown',
                    'message' => 'Showdown finalizado: Oponente venceu com Trinca.',
                    'winner' => [
                        'player' => 'opponent',
                        'label' => 'Oponente',
                        'handName' => 'Trinca',
                    ],
                ] : null,
                'persistence' => ['tableId' => $table->id, 'handId' => 1, 'syncVersion' => 1],
            ],
            'started_at' => now(),
            'finished_at' => $finished ? now() : null,
        ]);

        return $table;
    }
}
