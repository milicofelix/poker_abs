<?php

namespace Tests\Feature\Poker;

use App\Domain\Poker\Game\OpponentDecision;
use App\Domain\Poker\Game\PokerAction;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerBotMemoryAdaptationService;
use App\Services\Poker\PokerBotStatTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerBotMemoryAdaptationTest extends TestCase
{
    use RefreshDatabase;

    public function test_memoria_identifica_adversario_que_folda_demais(): void
    {
        [$table, $bot] = $this->createTableWithBot();
        $tracking = new PokerBotStatTrackingService();

        for ($i = 0; $i < 4; $i++) {
            $tracking->recordDecision(
                table: $table,
                bot: $bot,
                actor: 'opponent',
                state: ['street' => 'turn', 'amountToCall' => 0, 'currentBet' => 80, 'pot' => 260],
                handStrength: [
                    'score' => 48,
                    'range' => 'medium',
                    'label' => 'mão média',
                    'context' => [
                        'opponentAggressionRate' => 10,
                        'opponentFoldRate' => 75,
                    ],
                ],
                decision: new OpponentDecision(PokerAction::Raise, 140, 'Bot pressionou.'),
            );
        }

        $memory = (new PokerBotMemoryAdaptationService())->analyze(
            bot: $bot,
            state: ['actionHistory' => []],
            actor: 'opponent',
        );

        $this->assertSame('overfolder', $memory['opponentModel']);
        $this->assertSame('adversário folda demais', $memory['opponentModelLabel']);
        $this->assertGreaterThan(0, $memory['scoreAdjustment']);
        $this->assertGreaterThan(0, $memory['bluffPressure']);
    }

    public function test_memoria_da_mao_atual_identifica_adversario_agressivo_mesmo_sem_historico_persistido(): void
    {
        [, $bot] = $this->createTableWithBot();

        $memory = (new PokerBotMemoryAdaptationService())->analyze(
            bot: $bot,
            state: [
                'actionHistory' => [
                    ['actor' => 'player', 'action' => 'raise'],
                    ['actor' => 'player', 'action' => 'raise'],
                    ['actor' => 'player', 'action' => 'call'],
                ],
            ],
            actor: 'opponent',
        );

        $this->assertSame('aggressive_opponent', $memory['opponentModel']);
        $this->assertSame('adversário muito agressivo', $memory['opponentModelLabel']);
        $this->assertGreaterThan(0, $memory['callDownBias']);
    }

    /**
     * @return array{0: PokerTable, 1: PokerTablePlayer}
     */
    private function createTableWithBot(): array
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa memória bot',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $botUser = User::factory()->create(['name' => 'Bot LAG']);

        $bot = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $botUser->id,
            'nickname' => 'Bot LAG',
            'stack' => 1000,
            'seat_number' => 2,
            'status' => 'online',
            'is_bot' => true,
            'bot_profile' => 'lag',
            'bot_difficulty' => 'hard',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        return [$table, $bot];
    }
}
