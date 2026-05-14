<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTablePresenceService;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableLeaveController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        PokerTablePresenceService $presence,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
    ): JsonResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para sair da mesa.');

        $presence->leave($table, $user);

        $state = $pokerPersistence->currentStateForTable($table);

        if ($state) {
            $tableBroadcaster->broadcast($state);
        }

        return response()->json([
            'message' => 'Você saiu da mesa.',
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'state' => $state ? $privateState->forUser($table, $state, $user) : null,
        ]);
    }
}
