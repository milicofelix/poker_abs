<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Services\Poker\PokerRankingService;
use Inertia\Inertia;
use Inertia\Response;

final class PokerRankingController extends Controller
{
    public function __invoke(PokerRankingService $ranking): Response
    {
        return Inertia::render('Poker/Ranking', [
            'ranking' => $ranking->localRanking(),
        ]);
    }
}
