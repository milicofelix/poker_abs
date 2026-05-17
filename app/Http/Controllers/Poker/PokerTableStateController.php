<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerTableReadinessService;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableStateController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        PokerTablePresenceService $presence,
        PokerTableReadinessService $readiness,
        StartPokerHandAction $startPokerHand,
    ): JsonResponse {
        $presence->markCurrentUserOnline($table, $request->user());

        $state = $pokerPersistence->currentStateForTable($table);

        if (! $state) {
            $latestState = $pokerPersistence->latestStateForTable($table);

            $state = $latestState && (bool) ($latestState['isFinished'] ?? false)
                ? $latestState
                : $readiness->startIfReady($table, $startPokerHand, $pokerPersistence);
        }

        return response()->json([
            'state' => $privateState->forUser($table, $state, $request->user()),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
        ]);
    }
}
