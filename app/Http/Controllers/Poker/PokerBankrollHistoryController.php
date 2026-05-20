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
            ->where('user_id', $user->id)
            ->with([
                'table:id,name',
                'hand:id,street,status',
            ])
            ->latest('id')
            ->limit(50)
            ->get();

        $credits = (int) PokerBankrollTransaction::query()
            ->where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        $debits = (int) PokerBankrollTransaction::query()
            ->where('user_id', $user->id)
            ->where('amount', '<', 0)
            ->sum('amount');

        return Inertia::render('Poker/Bankroll', [
            'summary' => [
                'currentBalance' => (int) $user->poker_bankroll,
                'credits' => $credits,
                'debits' => abs($debits),
                'transactionsCount' => PokerBankrollTransaction::query()
                    ->where('user_id', $user->id)
                    ->count(),
            ],
            'transactions' => $transactions->map(static fn (PokerBankrollTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'typeLabel' => match ($transaction->type) {
                    PokerBankrollTransaction::TYPE_BUY_IN => 'Buy-in',
                    PokerBankrollTransaction::TYPE_PAYOUT => 'Premiação',
                    default => 'Movimentação',
                },
                'amount' => $transaction->amount,
                'balanceBefore' => $transaction->balance_before,
                'balanceAfter' => $transaction->balance_after,
                'tableName' => $transaction->table?->name,
                'handStatus' => $transaction->hand?->status,
                'createdAtLabel' => $transaction->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }
}
