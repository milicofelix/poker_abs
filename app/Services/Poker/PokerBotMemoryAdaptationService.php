<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerBotDecisionLog;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Support\Collection;

final class PokerBotMemoryAdaptationService
{
    /**
     * @param array<string, mixed> $state
     * @return array{
     *     sampleSize:int,
     *     opponentModel:string,
     *     opponentModelLabel:string,
     *     scoreAdjustment:int,
     *     bluffPressure:int,
     *     callDownBias:int,
     *     averageOpponentAggressionRate:int,
     *     averageOpponentFoldRate:int,
     *     recentOpponentAggressionRate:int,
     *     recentOpponentFoldRate:int,
     *     notes:array<int, string>
     * }
     */
    public function analyze(PokerTablePlayer $bot, array $state, string $actor): array
    {
        /** @var Collection<int, PokerBotDecisionLog> $logs */
        $logs = $bot->botDecisionLogs()
            ->latest()
            ->limit(30)
            ->get();

        $historyProfile = $this->profileFromLogs($logs);
        $recentProfile = $this->profileFromCurrentHand($state, $actor);
        $sampleSize = (int) $historyProfile['sampleSize'];
        $averageAggression = (int) $historyProfile['averageOpponentAggressionRate'];
        $averageFold = (int) $historyProfile['averageOpponentFoldRate'];
        $recentAggression = (int) $recentProfile['recentOpponentAggressionRate'];
        $recentFold = (int) $recentProfile['recentOpponentFoldRate'];

        $effectiveAggression = max($averageAggression, $recentAggression);
        $effectiveFold = max($averageFold, $recentFold);
        $scoreAdjustment = 0;
        $bluffPressure = 0;
        $callDownBias = 0;
        $opponentModel = 'unknown';
        $opponentModelLabel = 'sem leitura suficiente';
        $notes = [];

        if ($sampleSize >= 3 || (int) $recentProfile['recentOpponentActions'] >= 2) {
            if ($effectiveFold >= 50) {
                $opponentModel = 'overfolder';
                $opponentModelLabel = 'adversário folda demais';
                $scoreAdjustment += 7;
                $bluffPressure += 10;
                $notes[] = 'pressionar mais spots marginais porque o adversário larga muito';
            }

            if ($effectiveAggression >= 55) {
                $opponentModel = $opponentModel === 'overfolder' ? 'polarized' : 'aggressive_opponent';
                $opponentModelLabel = $opponentModel === 'polarized'
                    ? 'adversário polarizado'
                    : 'adversário muito agressivo';
                $scoreAdjustment += 4;
                $callDownBias += 10;
                $notes[] = 'pagar/induzir mais contra agressão frequente';
            }

            if ($opponentModel === 'unknown' && (int) $recentProfile['recentOpponentCallRate'] >= 55) {
                $opponentModel = 'calling_station_opponent';
                $opponentModelLabel = 'adversário paga demais';
                $scoreAdjustment += 3;
                $bluffPressure -= 6;
                $notes[] = 'reduzir blefes e apostar mais por valor';
            }
        }

        if ($notes === []) {
            $notes[] = 'memória ainda neutra, sem ajuste forte';
        }

        return [
            'sampleSize' => $sampleSize,
            'opponentModel' => $opponentModel,
            'opponentModelLabel' => $opponentModelLabel,
            'scoreAdjustment' => max(-12, min(18, $scoreAdjustment)),
            'bluffPressure' => max(-12, min(18, $bluffPressure)),
            'callDownBias' => max(0, min(18, $callDownBias)),
            'averageOpponentAggressionRate' => $averageAggression,
            'averageOpponentFoldRate' => $averageFold,
            'recentOpponentAggressionRate' => $recentAggression,
            'recentOpponentFoldRate' => $recentFold,
            'notes' => $notes,
        ];
    }

    /**
     * @param Collection<int, PokerBotDecisionLog> $logs
     * @return array{sampleSize:int,averageOpponentAggressionRate:int,averageOpponentFoldRate:int}
     */
    private function profileFromLogs(Collection $logs): array
    {
        $sampleSize = $logs->count();

        if ($sampleSize === 0) {
            return [
                'sampleSize' => 0,
                'averageOpponentAggressionRate' => 0,
                'averageOpponentFoldRate' => 0,
            ];
        }

        $aggressionValues = [];
        $foldValues = [];

        foreach ($logs as $log) {
            $context = is_array($log->context) ? $log->context : [];

            if (isset($context['opponentAggressionRate']) && is_numeric($context['opponentAggressionRate'])) {
                $aggressionValues[] = (int) $context['opponentAggressionRate'];
            }

            if (isset($context['opponentFoldRate']) && is_numeric($context['opponentFoldRate'])) {
                $foldValues[] = (int) $context['opponentFoldRate'];
            }
        }

        return [
            'sampleSize' => $sampleSize,
            'averageOpponentAggressionRate' => $this->average($aggressionValues),
            'averageOpponentFoldRate' => $this->average($foldValues),
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array{recentOpponentActions:int,recentOpponentAggressionRate:int,recentOpponentFoldRate:int,recentOpponentCallRate:int}
     */
    private function profileFromCurrentHand(array $state, string $actor): array
    {
        $history = is_array($state['actionHistory'] ?? null) ? $state['actionHistory'] : [];
        $opponentActor = $actor === 'opponent' ? 'player' : 'opponent';
        $recent = array_values(array_filter(
            array_slice($history, -10),
            static fn (mixed $action): bool => is_array($action) && (string) ($action['actor'] ?? '') === $opponentActor,
        ));

        $total = count($recent);
        $raises = count(array_filter($recent, static fn (array $action): bool => (string) ($action['action'] ?? '') === 'raise'));
        $folds = count(array_filter($recent, static fn (array $action): bool => (string) ($action['action'] ?? '') === 'fold'));
        $calls = count(array_filter($recent, static fn (array $action): bool => (string) ($action['action'] ?? '') === 'call'));

        return [
            'recentOpponentActions' => $total,
            'recentOpponentAggressionRate' => $total > 0 ? (int) round(($raises / $total) * 100) : 0,
            'recentOpponentFoldRate' => $total > 0 ? (int) round(($folds / $total) * 100) : 0,
            'recentOpponentCallRate' => $total > 0 ? (int) round(($calls / $total) * 100) : 0,
        ];
    }

    /**
     * @param array<int, int> $values
     */
    private function average(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        return (int) round(array_sum($values) / count($values));
    }
}
