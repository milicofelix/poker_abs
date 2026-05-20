<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerBankrollService;
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
        PokerBankrollService $bankroll,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
    ): JsonResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para sair da mesa.');

        $player = $table->realPlayers()
            ->where('user_id', $user->id)
            ->first();

        if ($player && $pokerPersistence->currentStateForTable($table) !== null) {
            return response()->json([
                'message' => 'Não é possível sair da mesa durante uma mão em andamento. Aguarde a mão terminar.',
            ], 422);
        }

        if ($player) {
            $bankroll->returnStackToBankroll($table, $player);
        }

        $presence->leave($table, $user);

        $state = $pokerPersistence->currentStateForTable($table);

        if ($state) {
            $tableBroadcaster->broadcast($state);
        }

        return response()->json([
            'message' => 'Você saiu da mesa.',
            'bankroll' => $user->fresh()->poker_bankroll,
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'state' => $state ? $privateState->forUser($table, $state, $user) : null,
        ]);
    }
}
