<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerTableRuntimeStateService;
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
        PokerTableRuntimeStateService $runtimeState,
    ): JsonResponse {
        $presence->markCurrentUserOnline($table, $request->user());

        $runtime = $runtimeState->resolve($table, $pokerPersistence);
        $state = $runtime['state'];

        return response()->json([
            'state' => $privateState->forUser($table, $state, $request->user()),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'stateContracts' => $runtime['stateContracts'],
            'engineMode' => $runtime['engineMode'],
            'multiSeatEnabled' => $runtime['multiSeatEnabled'],
        ]);
    }
}
