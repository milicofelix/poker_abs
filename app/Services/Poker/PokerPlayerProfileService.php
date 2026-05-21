<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\User;

final class PokerPlayerProfileService
{
    /**
     * FASE 12.9 — perfil público/financeiro do jogador.
     *
     * @return array<string, mixed>
     */
    public function profile(User $user): array
    {
        $freshUser = $user->fresh() ?? $user;
        $stats = $this->statsFor($freshUser);

        return [
            'phase' => '12.9',
            'player' => [
                'id' => $freshUser->id,
                'name' => $freshUser->name,
                'initials' => $this->initials($freshUser->name),
                'bankroll' => (int) ($freshUser->poker_bankroll ?? 0),
                'rankingPosition' => $this->rankingPosition($freshUser),
                'createdAt' => $freshUser->created_at?->format('d/m/Y'),
            ],
            'stats' => $stats,
            'recentTransactions' => $this->recentTransactions($freshUser),
            'recentHands' => $this->recentHands($freshUser),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statsFor(User $user): array
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

        $invested = $buyIns + $rebuys;
        $returned = $payouts + $stackReturns;
        $netProfit = $returned - $invested;
        $wins = $transactions
            ->where('type', PokerBankrollTransaction::TYPE_PAYOUT)
            ->where('amount', '>', 0)
            ->pluck('poker_hand_id')
            ->filter()
            ->unique()
            ->count();
        $playedHands = $transactions
            ->pluck('poker_hand_id')
            ->filter()
            ->unique()
            ->count();

        return [
            'buyIns' => $buyIns,
            'rebuys' => $rebuys,
            'invested' => $invested,
            'payouts' => $payouts,
            'stackReturns' => $stackReturns,
            'returned' => $returned,
            'netProfit' => $netProfit,
            'roi' => $invested > 0 ? round(($netProfit / $invested) * 100, 2) : null,
            'wins' => $wins,
            'playedHands' => $playedHands,
            'winRate' => $playedHands > 0 ? round(($wins / $playedHands) * 100, 2) : null,
            'transactions' => $transactions->count(),
            'lastMovementAt' => optional($transactions->sortByDesc('created_at')->first()?->created_at)->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(User $user): array
    {
        return PokerBankrollTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(12)
            ->get(['id', 'type', 'amount', 'balance_after', 'created_at'])
            ->map(fn (PokerBankrollTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'typeLabel' => $this->transactionLabel($transaction->type),
                'amount' => (int) $transaction->amount,
                'balanceAfter' => (int) $transaction->balance_after,
                'createdAt' => $transaction->created_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentHands(User $user): array
    {
        return PokerBankrollTransaction::query()
            ->with(['hand:id,pot,winner,winner_label,winning_hand_name,finished_at', 'table:id,name'])
            ->where('user_id', $user->id)
            ->whereNotNull('poker_hand_id')
            ->latest('id')
            ->limit(10)
            ->get()
            ->unique('poker_hand_id')
            ->values()
            ->map(fn (PokerBankrollTransaction $transaction): array => [
                'handId' => $transaction->poker_hand_id,
                'table' => $transaction->table?->name ?? 'Mesa removida',
                'pot' => (int) ($transaction->hand?->pot ?? 0),
                'winner' => $transaction->hand?->winner_label ?? $transaction->hand?->winner,
                'winningHand' => $transaction->hand?->winning_hand_name,
                'movement' => (int) $transaction->amount,
                'finishedAt' => $transaction->hand?->finished_at?->format('d/m/Y H:i')
                    ?? $transaction->created_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    private function rankingPosition(User $user): int
    {
        return User::query()
            ->where(function ($query) use ($user): void {
                $query
                    ->where('poker_bankroll', '>', (int) ($user->poker_bankroll ?? 0))
                    ->orWhere(function ($query) use ($user): void {
                        $query
                            ->where('poker_bankroll', (int) ($user->poker_bankroll ?? 0))
                            ->where('id', '<', $user->id);
                    });
            })
            ->count() + 1;
    }

    private function initials(string $name): string
    {
        $parts = collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)));

        return $parts->join('') ?: 'JP';
    }

    private function transactionLabel(string $type): string
    {
        return match ($type) {
            PokerBankrollTransaction::TYPE_BUY_IN => 'Buy-in',
            PokerBankrollTransaction::TYPE_REBUY => 'Rebuy',
            PokerBankrollTransaction::TYPE_PAYOUT => 'Premiação',
            PokerBankrollTransaction::TYPE_STACK_RETURN => 'Retorno de stack',
            default => $type,
        };
    }
}
