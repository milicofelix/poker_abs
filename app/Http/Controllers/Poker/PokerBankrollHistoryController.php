<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerBankrollTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerBankrollHistoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $transactions = PokerBankrollTransaction::query()
            ->with(['table:id,name', 'hand:id,created_at'])
            ->where('user_id', $user?->id)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (PokerBankrollTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'typeLabel' => $this->typeLabel($transaction->type),
                'amount' => $transaction->amount,
                'amountLabel' => $this->amountLabel($transaction->amount),
                'balanceBefore' => $transaction->balance_before,
                'balanceAfter' => $transaction->balance_after,
                'tableName' => $transaction->table?->name ?? 'Mesa removida',
                'handId' => $transaction->poker_hand_id,
                'createdAt' => $transaction->created_at?->format('d/m/Y H:i'),
                'metadata' => $transaction->metadata ?? [],
            ]);

        $credits = (int) PokerBankrollTransaction::query()
            ->where('user_id', $user?->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $debits = (int) PokerBankrollTransaction::query()
            ->where('user_id', $user?->id)
            ->where('amount', '<', 0)
            ->sum('amount');

        return Inertia::render('Poker/BankrollHistory', [
            'bankroll' => [
                'current' => (int) ($user?->poker_bankroll ?? 0),
                'credits' => $credits,
                'debits' => $debits,
                'net' => $credits + $debits,
                'transactionsCount' => PokerBankrollTransaction::query()
                    ->where('user_id', $user?->id)
                    ->count(),
            ],
            'transactions' => $transactions,
        ]);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            PokerBankrollTransaction::TYPE_BUY_IN => 'Buy-in',
            PokerBankrollTransaction::TYPE_PAYOUT => 'Premiação',
            default => $type,
        };
    }

    private function amountLabel(int $amount): string
    {
        $prefix = $amount > 0 ? '+' : '';

        return $prefix.number_format($amount, 0, ',', '.').' fichas';
    }
}
