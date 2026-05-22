<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTurnTimeoutService;
use App\Services\Poker\PokerTournamentRuntimeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableTurnTimeoutController extends Controller
{
    public function __invoke(
        Request $request,
        PokerTable $table,
        PokerTurnTimeoutService $timeoutService,
        PokerTournamentRuntimeSyncService $tournamentRuntimeSync,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
        MultiplayerPokerPrivateStateService $privateState,
    ): JsonResponse {
        $result = $timeoutService->process($table);

        if ($result['processed']) {
            $tournamentRuntimeSync->syncAfterStateChange($table, $result['state']);
            $tableBroadcaster->broadcast($result['state']);
        }

        $result['state'] = $privateState->forUser($table, $result['state'], $request->user());

        return response()->json($result);
    }
}
