<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\PlayLocalPokerRoundAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Poker\PlayPokerRoundRequest;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use Illuminate\Http\JsonResponse;

final class PokerTableRoundActionController extends Controller
{
    public function __invoke(
        PlayPokerRoundRequest $request,
        PokerTable $table,
        PlayLocalPokerRoundAction $playRound,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
        MultiplayerPokerPrivateStateService $privateState,
    ): JsonResponse {
        $currentState = $pokerPersistence->currentStateForTable($table);

        abort_if(! $currentState, 404, 'Mesa sem mão ativa.');

        $nextState = $playRound->execute(
            $currentState,
            $request->pokerAction(),
            $request->raiseAmount(),
        );

        $nextState = $pokerPersistence->persist($nextState);

        $tableBroadcaster->broadcast($nextState);

        return response()->json([
            'state' => $privateState->forUser($table, $nextState, $request->user()),
        ]);
    }
}
