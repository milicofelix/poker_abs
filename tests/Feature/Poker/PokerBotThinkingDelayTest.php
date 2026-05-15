<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerBotThinkingDelayTest extends TestCase
{
    use RefreshDatabase;

    public function test_delay_humanizado_do_bot_e_informado_no_payload_sem_atrasar_testes(): void
    {
        config(['poker.bot_thinking_seconds' => 5]);

        [$table, $user] = $this->createTableWithHumanAndBot();

        $startedAt = microtime(true);

        $this->actingAs($user)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.botDecision.processed', true)
            ->assertJsonPath('state.botDecision.actor', 'opponent')
            ->assertJsonPath('state.botDecision.thinkingDelaySeconds', 0)
            ->assertJsonPath('state.lastAction.isBot', true)
            ->assertJsonPath('state.lastAction.botThinkingDelaySeconds', 0);

        $elapsedSeconds = microtime(true) - $startedAt;

        $this->assertLessThan(
            2.0,
            $elapsedSeconds,
            'O delay humanizado não deve deixar a suíte lenta no ambiente de testes.'
        );
    }

    public function test_bot_continua_registrando_decisao_depois_do_delay_humanizado(): void
    {
        config(['poker.bot_thinking_seconds' => 5]);

        [$table, $user] = $this->createTableWithHumanAndBot();

        $this->actingAs($user)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.currentTurn.canonicalActor', 'player')
            ->assertJsonPath('state.lastAction.actor', 'opponent')
            ->assertJsonPath('state.botDecision.thinkingDelaySeconds', 0);

        $this->assertDatabaseHas('poker_bot_decision_logs', [
            'poker_table_id' => $table->id,
            'actor' => 'opponent',
            'profile' => 'conservative',
            'difficulty' => 'normal',
        ]);
    }

    /**
     * @return array{0: PokerTable, 1: User}
     */
    private function createTableWithHumanAndBot(): array
    {
        $user = User::factory()->create(['name' => 'Jogador Humano']);
        $botUser = User::factory()->create(['name' => 'Bot Conservador']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa delay bot',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

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
}
