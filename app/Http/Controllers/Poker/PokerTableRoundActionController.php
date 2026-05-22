<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poker\PlayPokerRoundRequest;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTableTurnActionService;
use App\Services\Poker\PokerBotTurnProcessor;
use App\Services\Poker\PokerMultiSeatTurnActionService;
use App\Services\Poker\PokerTournamentRuntimeSyncService;
use Illuminate\Http\JsonResponse;

final class PokerTableRoundActionController extends Controller
{
    public function __invoke(
        PlayPokerRoundRequest $request,
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
        MultiplayerPokerPrivateStateService $privateState,
        PokerTableTurnActionService $turnAction,
        PokerBotTurnProcessor $botTurnProcessor,
        PokerMultiSeatTurnActionService $multiSeatTurnAction,
        PokerTournamentRuntimeSyncService $tournamentRuntimeSync,
    ): JsonResponse {
        $currentState = $pokerPersistence->currentStateForTable($table);

        abort_if(! $currentState, 404, 'Mesa sem mão ativa.');

        if ((bool) data_get($currentState, 'multiSeat.enabled', false)) {
            $nextState = $multiSeatTurnAction->execute(
                $table,
                $currentState,
                $request->user(),
                $request->pokerAction(),
                $request->raiseAmount(),
            );
        } else {
            $nextState = $turnAction->execute(
                $table,
                $currentState,
                $request->user(),
                $request->pokerAction(),
                $request->raiseAmount(),
            );

            $nextState = $botTurnProcessor->process($table, $nextState);
        }

        $nextState = $pokerPersistence->persist($nextState);
        $tournamentRuntimeSync->syncAfterStateChange($table, $nextState);

        $tableBroadcaster->broadcast($nextState);

        return response()->json([
            'state' => $privateState->forUser($table, $nextState, $request->user()),
        ]);
    }
}
