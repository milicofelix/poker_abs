<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerHand;
use App\Services\Poker\PokerDetailedHandHistoryService;
use Inertia\Inertia;
use Inertia\Response;

final class PokerHandShowController extends Controller
{
    public function __construct(
        private readonly PokerDetailedHandHistoryService $history,
    ) {}

    public function __invoke(PokerHand $hand): Response
    {
        return Inertia::render('Poker/HandShow', [
            'hand' => $this->history->detail($hand),
        ]);
    }
}
