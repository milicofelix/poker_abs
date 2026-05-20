<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerBankrollTransaction;
use App\Services\Poker\PokerRankingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerRankingController extends Controller
{
    public function __invoke(Request $request, PokerRankingService $ranking): Response
    {
        $hasFinancialLedger = PokerBankrollTransaction::query()->exists();

        return Inertia::render('Poker/Ranking', [
            'ranking' => $hasFinancialLedger
                ? $ranking->financialRanking($request->user())
                : $ranking->localRanking(),
        ]);
    }
}
