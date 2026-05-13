<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Services\Poker\PokerStatisticsService;
use Inertia\Inertia;
use Inertia\Response;

final class PokerStatisticsController extends Controller
{
    public function __invoke(PokerStatisticsService $statisticsService): Response
    {
        return Inertia::render('Poker/Statistics', [
            'statistics' => $statisticsService->localStatistics(),
        ]);
    }
}
