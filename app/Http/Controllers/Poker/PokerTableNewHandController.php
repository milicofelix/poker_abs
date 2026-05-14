<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTablePresenceService;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableNewHandController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        StartPokerHandAction $startPokerHand,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        MultiplayerPokerTableStateBroadcaster $broadcaster,
        PokerTablePresenceService $presence,
    ): JsonResponse {
        $presence->markCurrentUserOnline($table, $request->user());

        abort_if(! $request->user(), 403, 'Você precisa estar autenticado para iniciar uma nova mão.');
        abort_if($table->realPlayers()->whereNotNull('seat_number')->count() < 2, 422, 'A mesa precisa de dois jogadores sentados para iniciar uma nova mão.');

        $state = $pokerPersistence->currentStateForTable($table);

        abort_if($state && ! (bool) ($state['isFinished'] ?? false), 422, 'A mão atual ainda está em andamento.');

        $nextState = $pokerPersistence->startOnTable($table, $startPokerHand->execute());

        $broadcaster->broadcast($nextState);

        return response()->json([
            'state' => $privateState->forUser($table, $nextState, $request->user()),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'message' => 'Nova mão iniciada para todos os jogadores.',
        ]);
    }
}
