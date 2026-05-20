<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerHand;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PokerRankingService
{
    /**
     * Mantém compatibilidade com o ranking local antigo baseado em mãos finalizadas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function localRanking(): array
    {
        $finishedHands = PokerHand::query()
            ->where('status', 'finished')
            ->whereNotNull('winner')
            ->latest('finished_at')
            ->get(['id', 'winner', 'winner_label', 'winning_hand_name', 'pot', 'finished_at']);

        /** @var Collection<string, Collection<int, PokerHand>> $grouped */
        $grouped = $finishedHands->groupBy(fn (PokerHand $hand): string => (string) $hand->winner);

        return $grouped
            ->map(fn (Collection $hands, string $winner): array => $this->serializeRankingRow($winner, $hands))
            ->sort(fn (array $first, array $second): int => [
                $second['victories'],
                $second['chipsWon'],
                $first['label'],
            ] <=> [
                $first['victories'],
                $first['chipsWon'],
                $second['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * FASE 12.8 — ranking financeiro baseado em bankroll e ledger.
     *
     * @return array<string, mixed>
     */
    public function financialRanking(?User $currentUser = null): array
    {
        $leaderboard = User::query()
            ->select(['id', 'name', 'poker_bankroll', 'created_at'])
            ->orderByDesc('poker_bankroll')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (User $user): array => $this->serializeFinancialRow($user))
            ->sortBy([
                ['bankroll', 'desc'],
                ['netProfit', 'desc'],
                ['wins', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->map(fn (array $row, int $index): array => array_merge($row, [
                'position' => $index + 1,
                'podiumLabel' => $this->podiumLabel($index + 1),
            ]))
            ->all();

        return [
            'phase' => '12.8',
            'title' => 'Ranking financeiro',
            'summary' => $this->summary(),
            'leaderboard' => $leaderboard,
            'currentUser' => $currentUser ? $this->currentUserPayload($currentUser, $leaderboard) : null,
            'recentTransactions' => $this->recentTransactions(),
            'legacyRanking' => $this->localRanking(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentUserPayload(User $user, array $leaderboard): array
    {
        $row = collect($leaderboard)->firstWhere('userId', $user->id) ?? $this->serializeFinancialRow($user->fresh());

        return [
            ...$row,
            'isCurrentUser' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFinancialRow(User $user): array
    {
        $stats = $this->transactionStatsForUser($user);
        $buyIns = $stats['buyIns'] + $stats['rebuys'];
        $returns = $stats['payouts'] + $stats['stackReturns'];
        $netProfit = $returns - $buyIns;

        return [
            'userId' => $user->id,
            'name' => $user->name,
            'bankroll' => (int) ($user->poker_bankroll ?? 0),
            'buyIns' => $buyIns,
            'payouts' => $stats['payouts'],
            'stackReturns' => $stats['stackReturns'],
            'netProfit' => $netProfit,
            'roi' => $buyIns > 0 ? round(($netProfit / $buyIns) * 100, 2) : null,
            'wins' => $stats['wins'],
            'transactions' => $stats['transactions'],
            'lastMovementAt' => $stats['lastMovementAt'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionStatsForUser(User $user): array
    {
        $transactions = PokerBankrollTransaction::query()
            ->where('user_id', $user->id)
            ->get(['type', 'amount', 'poker_hand_id', 'created_at']);

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

        return [
            'buyIns' => $buyIns,
            'rebuys' => $rebuys,
            'payouts' => $payouts,
            'stackReturns' => $stackReturns,
            'wins' => $transactions
                ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
                ->where('amount', '>', 0)
                ->pluck('poker_hand_id')
                ->filter()
                ->unique()
                ->count(),
            'transactions' => $transactions->count(),
            'lastMovementAt' => optional($transactions->sortByDesc('created_at')->first()?->created_at)->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(): array
    {
        $transactions = PokerBankrollTransaction::query()->get(['type', 'amount']);
        $playersCount = User::query()->count();
        $totalBankroll = (int) User::query()->sum('poker_bankroll');
        $highestBankroll = (int) User::query()->max('poker_bankroll');
        $buyIns = abs((int) $transactions
            ->whereIn('type', [PokerBankrollTransaction::TYPE_BUY_IN, PokerBankrollTransaction::TYPE_REBUY])
            ->sum('amount'));
        $payouts = (int) $transactions
            ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
            ->where('amount', '>', 0)
            ->sum('amount');

        return [
            'players' => $playersCount,
            'totalBankroll' => $totalBankroll,
            'highestBankroll' => $highestBankroll,
            'buyIns' => $buyIns,
            'payouts' => $payouts,
            'netResult' => $payouts - $buyIns,
            'transactions' => $transactions->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(): array
    {
        return PokerBankrollTransaction::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(static fn (PokerBankrollTransaction $transaction): array => [
                'id' => $transaction->id,
                'player' => $transaction->user?->name ?? 'Jogador removido',
                'type' => $transaction->type,
                'typeLabel' => match ($transaction->type) {
                    PokerBankrollTransaction::TYPE_BUY_IN => 'Buy-in',
                    PokerBankrollTransaction::TYPE_REBUY => 'Rebuy',
                    PokerBankrollTransaction::TYPE_PAYOUT => 'Premiação',
                    PokerBankrollTransaction::TYPE_STACK_RETURN => 'Retorno de stack',
                    default => $transaction->type,
                },
                'amount' => (int) $transaction->amount,
                'balanceAfter' => (int) $transaction->balance_after,
                'createdAt' => $transaction->created_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * @param Collection<int, PokerHand> $hands
     * @return array<string, mixed>
     */
    private function serializeRankingRow(string $winner, Collection $hands): array
    {
        $lastHand = $hands->first();
        $label = $lastHand?->winner_label ?? $this->fallbackLabel($winner);
        $victories = $winner === 'tie' ? 0 : $hands->count();

        return [
            'winner' => $winner,
            'label' => $label,
            'victories' => $victories,
            'ties' => $winner === 'tie' ? $hands->count() : 0,
            'hands' => $hands->count(),
            'chipsWon' => $hands->sum('pot'),
            'lastWinningHand' => $lastHand?->winning_hand_name,
            'lastFinishedAt' => $lastHand?->finished_at?->format('d/m/Y H:i'),
        ];
    }

    private function fallbackLabel(string $winner): string
    {
        return match ($winner) {
            'player' => 'Você',
            'opponent' => 'Oponente',
            'tie' => 'Empates',
            default => ucfirst($winner),
        };
    }

    private function podiumLabel(int $position): string
    {
        return match ($position) {
            1 => '1º lugar',
            2 => '2º lugar',
            3 => '3º lugar',
            default => $position.'º lugar',
        };
    }
}
