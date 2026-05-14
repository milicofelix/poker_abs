<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerBotsMesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_da_mesa_envia_configuracao_para_adicionar_bots(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.botUrl', route('poker.tables.bots', $table))
                ->where('table.botProfiles.0.key', 'conservative')
                ->where('table.botProfiles.1.key', 'aggressive')
                ->where('table.botDifficulties.1', 'normal')
                ->where('table.botDifficultyOptions.0.key', 'easy')
                ->where('table.botDifficultyOptions.1.label', 'Normal')
                ->where('table.botDifficultyOptions.2.key', 'hard'));
    }

    public function test_usuario_autenticado_consegue_adicionar_bot_em_assento_livre(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'conservative',
                'difficulty' => 'normal',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Bot Conservador entrou na mesa.')
            ->assertJsonPath('bot.isBot', true)
            ->assertJsonPath('bot.botProfile', 'conservative')
            ->assertJsonPath('bot.botDifficulty', 'normal')
            ->assertJsonPath('bot.botDifficultyLabel', 'Normal')
            ->assertJsonPath('bot.seatNumber', 2)
            ->assertJsonPath('seatSlots.1.status', 'occupied')
            ->assertJsonPath('seatSlots.1.player.isBot', true);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'nickname' => 'Bot Conservador',
            'seat_number' => 2,
            'is_bot' => true,
            'bot_profile' => 'conservative',
            'bot_difficulty' => 'normal',
        ]);
    }


    public function test_usuario_consegue_adicionar_bot_com_dificuldade_dificil(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'aggressive',
                'difficulty' => 'hard',
            ])
            ->assertOk()
            ->assertJsonPath('bot.botProfile', 'aggressive')
            ->assertJsonPath('bot.botDifficulty', 'hard')
            ->assertJsonPath('bot.botDifficultyLabel', 'Difícil')
            ->assertJsonPath('seatSlots.1.player.botDifficultyLabel', 'Difícil');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'seat_number' => 2,
            'is_bot' => true,
            'bot_profile' => 'aggressive',
            'bot_difficulty' => 'hard',
        ]);
    }

    public function test_dificuldade_de_bot_invalida_e_rejeitada(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'conservative',
                'difficulty' => 'impossivel',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('difficulty');
    }

    public function test_nao_adiciona_bot_quando_mesa_esta_sem_assento_livre(): void
    {
        $userOne = User::factory()->create(['name' => 'Jogador 1']);
        $userTwo = User::factory()->create(['name' => 'Jogador 2']);
        $table = $this->createTable();

        $this->seatPlayer($table, $userOne, 1);
        $this->seatPlayer($table, $userTwo, 2);

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'aggressive',
                'difficulty' => 'normal',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Não existe assento livre para adicionar um bot nesta mesa.');
    }

    public function test_perfil_de_bot_invalido_e_rejeitado(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'maluco',
                'difficulty' => 'normal',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile');
    }


    public function test_bot_age_automaticamente_quando_a_vez_chega_nele(): void
    {
        [$table, $user] = $this->createTableWithHumanAndBot();

        $this->actingAs($user)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.currentTurn.canonicalActor', 'player')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', true)
            ->assertJsonPath('state.lastAction.actor', 'opponent')
            ->assertJsonPath('state.lastAction.isBot', true)
            ->assertJsonPath('state.botDecision.processed', true)
            ->assertJsonPath('state.botDecision.actor', 'opponent')
            ->assertJsonPath('state.botDecision.handStrength.label', 'mão jogável');
    }

    private function createTable(): PokerTable
    {
        return PokerTable::query()->create([
            'name' => 'Mesa com bots',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);
    }

    /**
     * @return array{0: PokerTable, 1: User}
     */
    private function createTableWithHumanAndBot(): array
    {
        $user = User::factory()->create(['name' => 'Jogador Humano']);
        $botUser = User::factory()->create(['name' => 'Bot Conservador']);
        $table = $this->createTable();

        $this->seatPlayer($table, $user, 1);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $botUser->id,
            'nickname' => 'Bot Conservador',
            'stack' => 1000,
            'seat_number' => 2,
            'status' => 'online',
            'is_bot' => true,
            'bot_profile' => 'conservative',
            'bot_difficulty' => 'normal',
            'joined_at' => now()->addSecond(),
            'last_seen_at' => now(),
        ]);

        $canonicalPlayer = $table->players()->create([
            'seat' => 1,
            'name' => 'Jogador Humano',
            'type' => 'real_user',
            'stack' => 990,
        ]);

        $canonicalOpponent = $table->players()->create([
            'seat' => 2,
            'name' => 'Bot Conservador',
            'type' => 'simple_bot',
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
            'role' => 'simple_bot',
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

        return [$table, $user];
    }

    private function seatPlayer(PokerTable $table, User $user, int $seatNumber): void
    {
        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => $seatNumber,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
