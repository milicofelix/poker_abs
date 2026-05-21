<?php

namespace Tests\Feature\Poker;

use App\Domain\Poker\Game\OpponentDecision;
use App\Domain\Poker\Game\PokerAction;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerBotStatTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerBotStatTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_decisao_do_bot_com_contexto_da_mao(): void
    {
        [$table, $bot] = $this->createTableWithBot();
        $service = new PokerBotStatTrackingService();

        $service->recordDecision(
            table: $table,
            bot: $bot,
            actor: 'opponent',
            state: [
                'street' => 'turn',
                'amountToCall' => 60,
                'currentBet' => 100,
                'pot' => 220,
            ],
            handStrength: [
                'score' => 68,
                'range' => 'strong',
                'label' => 'flush draw',
                'boardTexture' => 'dangerous',
                'hasFlushDraw' => true,
                'hasStraightDraw' => false,
                'drawBonus' => 12,
                'pressureBonus' => 5,
            ],
            decision: new OpponentDecision(PokerAction::Raise, 160, 'Bot pressionou com draw.'),
        );

        $this->assertDatabaseHas('poker_bot_decision_logs', [
            'poker_table_id' => $table->id,
            'poker_table_player_id' => $bot->id,
            'actor' => 'opponent',
            'profile' => 'aggressive',
            'difficulty' => 'hard',
            'street' => 'turn',
            'action' => 'raise',
            'amount' => 160,
            'score' => 68,
            'range' => 'strong',
            'label' => 'flush draw',
            'board_texture' => 'dangerous',
            'has_flush_draw' => true,
            'has_straight_draw' => false,
        ]);
    }

    public function test_resume_tendencia_recente_do_bot(): void
    {
        [$table, $bot] = $this->createTableWithBot();
        $service = new PokerBotStatTrackingService();

        foreach ([PokerAction::Raise, PokerAction::Raise, PokerAction::Call, PokerAction::Fold] as $index => $action) {
            $service->recordDecision(
                table: $table,
                bot: $bot,
                actor: 'opponent',
                state: ['street' => 'flop', 'amountToCall' => 20, 'currentBet' => 40, 'pot' => 100],
                handStrength: [
                    'score' => 50 + $index,
                    'range' => 'medium',
                    'label' => 'mão jogável',
                    'memory' => [
                        'opponentModel' => 'overfolder',
                        'opponentModelLabel' => 'adversário folda demais',
                    ],
                ],
                decision: new OpponentDecision($action, $action === PokerAction::Raise ? 90 : 20, 'Decisão de teste.'),
            );
        }

        $summary = $service->summarizeForBot($bot);

        $this->assertSame(4, $summary['totalDecisions']);
        $this->assertSame(75, $summary['aggressionRate']);
        $this->assertSame(25, $summary['foldRate']);
        $this->assertSame(25, $summary['callRate']);
        $this->assertSame(50, $summary['raiseRate']);
        $this->assertSame('pressionando', $summary['tendency']);
        $this->assertSame('adversário folda demais', $summary['memory']);
    }

    /**
     * @return array{0: PokerTable, 1: PokerTablePlayer}
     */
    private function createTableWithBot(): array
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa tracking bot',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $user = User::factory()->create(['name' => 'Bot Agressivo']);

        $bot = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => 'Bot Agressivo',
            'stack' => 1000,
            'seat_number' => 2,
            'status' => 'online',
            'is_bot' => true,
            'bot_profile' => 'aggressive',
            'bot_difficulty' => 'hard',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        return [$table, $bot];
    }
}
