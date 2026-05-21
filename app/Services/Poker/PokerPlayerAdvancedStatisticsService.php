<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerBankrollTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class PokerPlayerAdvancedStatisticsService
{
    /**
     * FASE 12.11.6 — Estatísticas avançadas com gráficos financeiros e distribuição de ações.
     *
     * @return array<string, mixed>
     */
    public function calculate(User $user, string $period = 'all'): array
    {
        $period = $this->normalizePeriod($period);
        $startDate = $this->startDateFor($period);

        $transactions = $this->transactionsFor($user, $startDate);

        $handIds = $transactions
            ->pluck('poker_hand_id')
            ->filter()
            ->unique()
            ->values();

        $tablePlayerIds = $transactions
            ->pluck('poker_table_player_id')
            ->filter()
            ->unique()
            ->values();

        $actions = $this->actionsFor($handIds, $tablePlayerIds, $startDate);

        $handsPlayed = $handIds->count();
        $handsWon = $transactions
            ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
            ->where('amount', '>', 0)
            ->pluck('poker_hand_id')
            ->filter()
            ->unique()
            ->count();

        $buyIns = abs((int) $transactions
            ->where('type', PokerBankrollTransaction::TYPE_BUY_IN)
            ->sum('amount'));
        $rebuys = abs((int) $transactions
            ->where('type', PokerBankrollTransaction::TYPE_REBUY)
            ->sum('amount'));
        $payouts = (int) $transactions
            ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
            ->where('amount', '>', 0)
            ->sum('amount');
        $stackReturns = (int) $transactions
            ->where('type', PokerBankrollTransaction::TYPE_STACK_RETURN)
            ->where('amount', '>', 0)
            ->sum('amount');

        $invested = $buyIns + $rebuys;
        $returned = $payouts + $stackReturns;
        $netProfit = $returned - $invested;
        $totalActions = $actions->count();
        $folds = $this->countActions($actions, ['fold', 'desistir', 'desistencia', 'desistência']);
        $allIns = $actions->filter(fn (PokerActionLog $action): bool => $this->isAllIn($action))->count();

        return [
            'phase' => '12.11.9',
            'period' => $period,
            'periodLabel' => $this->periodLabel($period),
            'cards' => [
                ['label' => 'Mãos jogadas', 'value' => $handsPlayed, 'helper' => 'Com movimentação no bankroll'],
                ['label' => 'Mãos vencidas', 'value' => $handsWon, 'helper' => 'Com premiação positiva'],
                ['label' => 'Winrate', 'value' => $this->percentage($handsWon, $handsPlayed), 'suffix' => '%', 'helper' => 'Vitórias / mãos jogadas'],
                ['label' => 'Fold rate', 'value' => $this->percentage($folds, $totalActions), 'suffix' => '%', 'helper' => 'Folds / ações registradas'],
                ['label' => 'All-in rate', 'value' => $this->percentage($allIns, $totalActions), 'suffix' => '%', 'helper' => 'All-ins / ações registradas'],
                ['label' => 'ROI', 'value' => $invested > 0 ? round(($netProfit / $invested) * 100, 2) : null, 'suffix' => '%', 'helper' => 'Lucro / investido'],
            ],
            'summary' => [
                'handsPlayed' => $handsPlayed,
                'handsWon' => $handsWon,
                'totalActions' => $totalActions,
                'folds' => $folds,
                'allIns' => $allIns,
                'buyIns' => $buyIns,
                'rebuys' => $rebuys,
                'invested' => $invested,
                'payouts' => $payouts,
                'stackReturns' => $stackReturns,
                'returned' => $returned,
                'netProfit' => $netProfit,
                'winRate' => $this->percentage($handsWon, $handsPlayed),
                'foldRate' => $this->percentage($folds, $totalActions),
                'allInRate' => $this->percentage($allIns, $totalActions),
                'roi' => $invested > 0 ? round(($netProfit / $invested) * 100, 2) : null,
            ],
            'charts' => $this->charts($transactions, $actions, $handsPlayed, $handsWon, $invested, $returned, $netProfit),
            'periodComparison' => $this->periodComparison($user, $period, [
                'handsPlayed' => $handsPlayed,
                'handsWon' => $handsWon,
                'invested' => $invested,
                'returned' => $returned,
                'netProfit' => $netProfit,
                'winRate' => $this->percentage($handsWon, $handsPlayed),
                'roi' => $invested > 0 ? round(($netProfit / $invested) * 100, 2) : null,
            ]),
            'periodOptions' => $this->periodOptions(),
        ];
    }


    /**
     * @return Collection<int, PokerBankrollTransaction>
     */
    private function transactionsFor(User $user, ?Carbon $startDate, ?Carbon $endDate = null): Collection
    {
        return PokerBankrollTransaction::query()
            ->with('hand:id,finished_at,created_at')
            ->where('user_id', $user->id)
            ->when($startDate || $endDate, function ($query) use ($startDate, $endDate): void {
                $query->where(function ($periodQuery) use ($startDate, $endDate): void {
                    $periodQuery
                        ->whereHas('hand', function ($handQuery) use ($startDate, $endDate): void {
                            $handQuery->where(function ($handDateQuery) use ($startDate, $endDate): void {
                                $handDateQuery
                                    ->where(function ($finishedDateQuery) use ($startDate, $endDate): void {
                                        $this->applyDateRange($finishedDateQuery, 'finished_at', $startDate, $endDate);
                                    })
                                    ->orWhere(function ($fallbackDateQuery) use ($startDate, $endDate): void {
                                        $fallbackDateQuery->whereNull('finished_at');
                                        $this->applyDateRange($fallbackDateQuery, 'created_at', $startDate, $endDate);
                                    });
                            });
                        })
                        ->orWhere(function ($fallbackQuery) use ($startDate, $endDate): void {
                            $fallbackQuery->whereNull('poker_hand_id');
                            $this->applyDateRange($fallbackQuery, 'created_at', $startDate, $endDate);
                        });
                });
            })
            ->get(['id', 'type', 'amount', 'poker_hand_id', 'poker_table_player_id', 'created_at']);
    }

    private function applyDateRange($query, string $column, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->where($column, '<', $endDate);
        }
    }

    /**
     * @param Collection<int, PokerBankrollTransaction> $transactions
     * @return array<string, mixed>
     */
    private function charts(Collection $transactions, Collection $actions, int $handsPlayed, int $handsWon, int $invested, int $returned, int $netProfit): array
    {
        $daily = $transactions
            ->groupBy(fn (PokerBankrollTransaction $transaction): string => $this->chartDateFor($transaction)->format('Y-m-d'))
            ->sortKeys();

        $runningProfit = 0;
        $runningInvested = 0;
        $runningReturned = 0;

        $profitSeries = [];
        $bankrollSeries = [];
        $handsSeries = [];

        foreach ($daily as $date => $dayTransactions) {
            $dayInvested = abs((int) $dayTransactions
                ->whereIn('type', [PokerBankrollTransaction::TYPE_BUY_IN, PokerBankrollTransaction::TYPE_REBUY])
                ->sum('amount'));
            $dayReturned = (int) $dayTransactions
                ->whereIn('type', [PokerBankrollTransaction::TYPE_PAYOUT, PokerBankrollTransaction::TYPE_STACK_RETURN])
                ->where('amount', '>', 0)
                ->sum('amount');
            $dayProfit = $dayReturned - $dayInvested;
            $dayHandsPlayed = $dayTransactions
                ->pluck('poker_hand_id')
                ->filter()
                ->unique()
                ->count();
            $dayHandsWon = $dayTransactions
                ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
                ->where('amount', '>', 0)
                ->pluck('poker_hand_id')
                ->filter()
                ->unique()
                ->count();

            $runningProfit += $dayProfit;
            $runningInvested += $dayInvested;
            $runningReturned += $dayReturned;

            $label = Carbon::parse($date)->format('d/m');

            $profitSeries[] = [
                'date' => $date,
                'label' => $label,
                'profit' => $dayProfit,
                'cumulativeProfit' => $runningProfit,
            ];

            $bankrollSeries[] = [
                'date' => $date,
                'label' => $label,
                'invested' => $runningInvested,
                'returned' => $runningReturned,
                'net' => $runningProfit,
            ];

            $handsSeries[] = [
                'date' => $date,
                'label' => $label,
                'played' => $dayHandsPlayed,
                'won' => $dayHandsWon,
                'winRate' => $this->percentage($dayHandsWon, $dayHandsPlayed),
            ];
        }

        return [
            'profitByPeriod' => $profitSeries,
            'bankrollEvolution' => $bankrollSeries,
            'handsPerformance' => $handsSeries,
            'actionDistribution' => $this->actionDistribution($actions),
            'financialEfficiency' => $this->financialEfficiency($handsPlayed, $handsWon, $invested, $returned, $netProfit),
            'empty' => count($profitSeries) === 0,
        ];
    }


    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array{label: string, count: int, percentage: ?float}>
     */
    private function actionDistribution(Collection $actions): array
    {
        $labels = [
            'check' => 'Check',
            'call' => 'Call',
            'raise' => 'Raise',
            'bet' => 'Bet',
            'fold' => 'Fold',
            'all-in' => 'All-in',
        ];

        $buckets = array_fill_keys(array_keys($labels), 0);

        foreach ($actions as $action) {
            $key = $this->actionBucket($action);
            $buckets[$key] = ($buckets[$key] ?? 0) + 1;
        }

        $total = array_sum($buckets);

        return collect($buckets)
            ->map(fn (int $count, string $key): array => [
                'label' => $labels[$key] ?? ucfirst($key),
                'count' => $count,
                'percentage' => $this->percentage($count, $total),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int|float|null>
     */
    private function financialEfficiency(int $handsPlayed, int $handsWon, int $invested, int $returned, int $netProfit): array
    {
        return [
            'averageInvestedPerHand' => $handsPlayed > 0 ? round($invested / $handsPlayed, 2) : null,
            'averageReturnedPerHand' => $handsPlayed > 0 ? round($returned / $handsPlayed, 2) : null,
            'averageProfitPerHand' => $handsPlayed > 0 ? round($netProfit / $handsPlayed, 2) : null,
            'conversionRate' => $this->percentage($returned, max(1, $invested)),
            'winRate' => $this->percentage($handsWon, $handsPlayed),
        ];
    }

    private function actionBucket(PokerActionLog $action): string
    {
        if ($this->isAllIn($action)) {
            return 'all-in';
        }

        $normalized = $this->normalizeAction($action->action);

        return match (true) {
            str_contains($normalized, 'fold') || str_contains($normalized, 'desist') => 'fold',
            str_contains($normalized, 'raise') || str_contains($normalized, 'aument') => 'raise',
            str_contains($normalized, 'bet') || str_contains($normalized, 'apost') => 'bet',
            str_contains($normalized, 'call') || str_contains($normalized, 'pagar') => 'call',
            str_contains($normalized, 'check') || str_contains($normalized, 'mesa') => 'check',
            default => 'call',
        };
    }

    private function chartDateFor(PokerBankrollTransaction $transaction): Carbon
    {
        $handDate = $transaction->hand?->finished_at ?? $transaction->hand?->created_at;

        return ($handDate ?? $transaction->created_at ?? now())->copy()->startOfDay();
    }

    /**
     * @param Collection<int, int> $handIds
     * @param Collection<int, int> $tablePlayerIds
     * @return Collection<int, PokerActionLog>
     */
    private function actionsFor(Collection $handIds, Collection $tablePlayerIds, ?Carbon $startDate): Collection
    {
        if ($handIds->isEmpty() || $tablePlayerIds->isEmpty()) {
            return collect();
        }

        return PokerActionLog::query()
            ->whereIn('poker_hand_id', $handIds->all())
            ->whereIn('poker_player_id', $tablePlayerIds->all())
            ->when($startDate, fn ($query) => $query->where('acted_at', '>=', $startDate))
            ->get(['poker_player_id', 'action', 'amount', 'metadata'])
            ->filter(function (PokerActionLog $action) use ($tablePlayerIds): bool {
                $metadata = $action->metadata ?? [];
                $metadataPlayerId = $metadata['poker_table_player_id'] ?? $metadata['table_player_id'] ?? null;

                if ($metadataPlayerId !== null) {
                    return $tablePlayerIds->contains((int) $metadataPlayerId);
                }

                return $tablePlayerIds->contains((int) $action->poker_player_id);
            })
            ->values();
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @param array<int, string> $expected
     */
    private function countActions(Collection $actions, array $expected): int
    {
        return $actions
            ->filter(fn (PokerActionLog $action): bool => in_array($this->normalizeAction($action->action), $expected, true))
            ->count();
    }

    private function isAllIn(PokerActionLog $action): bool
    {
        $metadata = $action->metadata ?? [];

        if (($metadata['is_all_in'] ?? false) || ($metadata['isAllIn'] ?? false)) {
            return true;
        }

        return in_array($this->normalizeAction($action->action), ['all-in', 'all in', 'allin'], true);
    }

    private function normalizeAction(?string $action): string
    {
        $action = mb_strtolower((string) $action);

        return str_replace(['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $action);
    }

    private function percentage(int $part, int $total): ?float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : null;
    }

    private function normalizePeriod(string $period): string
    {
        return in_array($period, ['7d', '30d', '90d', 'all'], true) ? $period : '30d';
    }

    private function startDateFor(string $period): ?Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => null,
        };
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            '7d' => 'Últimos 7 dias',
            '30d' => 'Últimos 30 dias',
            '90d' => 'Últimos 90 dias',
            default => 'Todo o histórico',
        };
    }


    /**
     * @param array<string, int|float|null> $currentSummary
     * @return array<string, mixed>
     */
    private function periodComparison(User $user, string $period, array $currentSummary): array
    {
        $days = $this->daysForPeriod($period);

        if ($days === null) {
            return [
                'enabled' => false,
                'message' => 'Comparativo disponível para filtros de 7, 30 ou 90 dias.',
                'current' => $currentSummary,
                'previous' => null,
                'delta' => null,
            ];
        }

        $previousEnd = now()->subDays($days);
        $previousStart = now()->subDays($days * 2);
        $previousSummary = $this->financialSummaryFor($this->transactionsFor($user, $previousStart, $previousEnd));

        return [
            'enabled' => true,
            'label' => 'Comparativo com período anterior',
            'currentLabel' => $this->periodLabel($period),
            'previousLabel' => 'Período anterior de '.$days.' dias',
            'current' => $currentSummary,
            'previous' => $previousSummary,
            'delta' => [
                'handsPlayed' => $this->delta((int) $currentSummary['handsPlayed'], (int) $previousSummary['handsPlayed']),
                'handsWon' => $this->delta((int) $currentSummary['handsWon'], (int) $previousSummary['handsWon']),
                'netProfit' => $this->delta((int) $currentSummary['netProfit'], (int) $previousSummary['netProfit']),
                'roi' => $this->deltaNullable($currentSummary['roi'], $previousSummary['roi']),
                'winRate' => $this->deltaNullable($currentSummary['winRate'], $previousSummary['winRate']),
            ],
        ];
    }

    /**
     * @param Collection<int, PokerBankrollTransaction> $transactions
     * @return array<string, int|float|null>
     */
    private function financialSummaryFor(Collection $transactions): array
    {
        $handsPlayed = $transactions
            ->pluck('poker_hand_id')
            ->filter()
            ->unique()
            ->count();
        $handsWon = $transactions
            ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
            ->where('amount', '>', 0)
            ->pluck('poker_hand_id')
            ->filter()
            ->unique()
            ->count();
        $invested = abs((int) $transactions
            ->whereIn('type', [PokerBankrollTransaction::TYPE_BUY_IN, PokerBankrollTransaction::TYPE_REBUY])
            ->sum('amount'));
        $returned = (int) $transactions
            ->whereIn('type', [PokerBankrollTransaction::TYPE_PAYOUT, PokerBankrollTransaction::TYPE_STACK_RETURN])
            ->where('amount', '>', 0)
            ->sum('amount');
        $netProfit = $returned - $invested;

        return [
            'handsPlayed' => $handsPlayed,
            'handsWon' => $handsWon,
            'invested' => $invested,
            'returned' => $returned,
            'netProfit' => $netProfit,
            'winRate' => $this->percentage($handsWon, $handsPlayed),
            'roi' => $invested > 0 ? round(($netProfit / $invested) * 100, 2) : null,
        ];
    }

    /**
     * @return array{value: int|float, direction: string}
     */
    private function delta(int|float $current, int|float $previous): array
    {
        $value = round($current - $previous, 2);

        if (is_int($current) && is_int($previous)) {
            $value = (int) $value;
        }

        return [
            'value' => $value,
            'direction' => $value > 0 ? 'up' : ($value < 0 ? 'down' : 'flat'),
        ];
    }

    /**
     * @return array{value: float|null, direction: string}
     */
    private function deltaNullable(int|float|null $current, int|float|null $previous): array
    {
        if ($current === null || $previous === null) {
            return ['value' => null, 'direction' => 'flat'];
        }

        return $this->delta((float) $current, (float) $previous);
    }

    private function daysForPeriod(string $period): ?int
    {
        return match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => null,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function periodOptions(): array
    {
        return [
            ['value' => '7d', 'label' => '7 dias'],
            ['value' => '30d', 'label' => '30 dias'],
            ['value' => '90d', 'label' => '90 dias'],
            ['value' => 'all', 'label' => 'Geral'],
        ];
    }
}
