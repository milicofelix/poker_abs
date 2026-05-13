<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerHand;
use App\Services\Poker\PokerHandReplayService;
use Inertia\Inertia;
use Inertia\Response;

final class PokerHandReplayController extends Controller
{
    public function __invoke(PokerHand $hand, PokerHandReplayService $replayService): Response
    {
        return Inertia::render('Poker/HandReplay', [
            'replay' => $replayService->build($hand),
        ]);
    }
}
