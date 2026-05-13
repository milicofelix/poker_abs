<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\PlayLocalPokerRoundAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Poker\PlayPokerRoundRequest;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\LocalPokerSessionService;
use Illuminate\Http\JsonResponse;

final class PokerRoundActionController extends Controller
{
    public function __invoke(
        PlayPokerRoundRequest $request,
        PlayLocalPokerRoundAction $playRound,
        LocalPokerSessionService $pokerSession,
        LocalPokerPersistenceService $pokerPersistence,
    ): JsonResponse {
        $nextState = $playRound->execute(
            $request->state($pokerSession->current() ?? []),
            $request->pokerAction(),
            $request->raiseAmount(),
        );

        $nextState = $pokerPersistence->persist($nextState);

        $pokerSession->store($nextState);

        return response()->json([
            'state' => $nextState,
        ]);
    }
}
