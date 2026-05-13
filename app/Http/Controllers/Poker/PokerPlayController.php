<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Services\Poker\LocalPokerSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerPlayController extends Controller
{
    public function __invoke(
        Request $request,
        StartPokerHandAction $startPokerHand,
        LocalPokerSessionService $pokerSession,
    ): Response {
        if ($request->boolean('new')) {
            $pokerSession->forget();
        }

        $hand = $pokerSession->current();

        if (! $hand) {
            $hand = $pokerSession->store($startPokerHand->execute());
        }

        return Inertia::render('Poker/Play', [
            'hand' => $hand,
        ]);
    }
}
