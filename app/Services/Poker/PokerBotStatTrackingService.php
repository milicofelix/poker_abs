<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\OpponentDecision;
use App\Models\Poker\PokerBotDecisionLog;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Support\Collection;

final class PokerBotStatTrackingService
{
    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $handStrength
     */
    public function recordDecision(
        PokerTable $table,
        PokerTablePlayer $bot,
        string $actor,
        array $state,
        array $handStrength,
        OpponentDecision $decision,
    ): PokerBotDecisionLog {
        return PokerBotDecisionLog::query()->create([
            'poker_table_id' => $table->id,
            'poker_table_player_id' => $bot->id,
            'actor' => $actor,
            'profile' => (string) ($bot->bot_profile ?: 'conservative'),
            'difficulty' => (string) ($bot->bot_difficulty ?: 'normal'),
            'street' => (string) ($state['street'] ?? 'pre_flop'),
            'action' => $decision->action->value,
            'amount' => max(0, (int) $decision->amount),
            'score' => max(0, min(100, (int) ($handStrength['score'] ?? 0))),
            'range' => $this->nullableString($handStrength['range'] ?? null),
            'label' => $this->nullableString($handStrength['label'] ?? null),
            'board_texture' => $this->nullableString($handStrength['boardTexture'] ?? null),
            'has_flush_draw' => (bool) ($handStrength['hasFlushDraw'] ?? false),
            'has_straight_draw' => (bool) ($handStrength['hasStraightDraw'] ?? false),
            'context' => array_merge(
                is_array($handStrength['context'] ?? null) ? $handStrength['context'] : [],
                [
                    'amountToCall' => (int) ($state['amountToCall'] ?? 0),
                    'currentBet' => (int) ($state['currentBet'] ?? 0),
                    'pot' => (int) ($state['pot'] ?? 0),
                    'drawBonus' => (int) ($handStrength['drawBonus'] ?? 0),
                    'pressureBonus' => (int) ($handStrength['pressureBonus'] ?? 0),
                    'probability' => is_array($handStrength['probability'] ?? null) ? $handStrength['probability'] : [],
                    'outs' => (int) ($handStrength['outs'] ?? 0),
                    'equity' => (int) ($handStrength['equity'] ?? 0),
                    'potOdds' => (int) ($handStrength['potOdds'] ?? 0),
                    'evScore' => (int) ($handStrength['evScore'] ?? 0),
                    'memoryAdjustment' => (int) ($handStrength['memoryAdjustment'] ?? 0),
                    'bluffPressure' => (int) ($handStrength['bluffPressure'] ?? 0),
                    'callDownBias' => (int) ($handStrength['callDownBias'] ?? 0),
                    'memory' => is_array($handStrength['memory'] ?? null) ? $handStrength['memory'] : [],
                ],
            ),
        ]);
    }

    /**
     * @return array{totalDecisions:int, aggressionRate:int, foldRate:int, callRate:int, raiseRate:int, averageScore:int, lastAction:?string, tendency:string, memory:string}
     */
    public function summarizeForBot(PokerTablePlayer $bot, int $limit = 20): array
    {
        /** @var Collection<int, PokerBotDecisionLog> $logs */
        $logs = $bot->botDecisionLogs()
            ->latest()
            ->limit(max(1, $limit))
            ->get();

        $total = $logs->count();

        if ($total === 0) {
            return [
                'totalDecisions' => 0,
                'aggressionRate' => 0,
                'foldRate' => 0,
                'callRate' => 0,
                'raiseRate' => 0,
                'averageScore' => 0,
                'lastAction' => null,
                'tendency' => 'sem histórico',
                'memory' => 'sem memória suficiente',
            ];
        }

        $raises = $logs->where('action', 'raise')->count();
        $calls = $logs->where('action', 'call')->count();
        $folds = $logs->where('action', 'fold')->count();
        $checks = $logs->where('action', 'check')->count();
        $aggressiveActions = $raises + $calls;

        $aggressionRate = $this->percent($aggressiveActions, $total);
        $raiseRate = $this->percent($raises, $total);
        $callRate = $this->percent($calls, $total);
        $foldRate = $this->percent($folds, $total);
        $averageScore = (int) round((float) $logs->avg('score'));

        return [
            'totalDecisions' => $total,
            'aggressionRate' => $aggressionRate,
            'foldRate' => $foldRate,
            'callRate' => $callRate,
            'raiseRate' => $raiseRate,
            'averageScore' => $averageScore,
            'lastAction' => $logs->first()?->action,
            'tendency' => $this->tendency($raiseRate, $foldRate, $checks, $total),
            'memory' => $this->memorySummary($logs),
        ];
    }


    /**
     * @param Collection<int, PokerBotDecisionLog> $logs
     */
    private function memorySummary(Collection $logs): string
    {
        foreach ($logs as $log) {
            $context = is_array($log->context) ? $log->context : [];
            $memory = is_array($context['memory'] ?? null) ? $context['memory'] : [];
            $model = (string) ($memory['opponentModel'] ?? 'unknown');

            if ($model !== 'unknown') {
                return (string) ($memory['opponentModelLabel'] ?? 'adaptação ativa');
            }
        }

        return 'memória neutra';
    }

    private function percent(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }

    private function tendency(int $raiseRate, int $foldRate, int $checks, int $total): string
    {
        if ($raiseRate >= 35) {
            return 'pressionando';
        }

        if ($foldRate >= 45) {
            return 'recuando';
        }

        if ($checks >= max(2, (int) ceil($total / 2))) {
            return 'controlando pote';
        }

        return 'equilibrado';
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
