<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerTableReadinessService;
use App\Services\Poker\PokerBotTurnProcessor;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableNewHandController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        MultiplayerPokerTableStateBroadcaster $broadcaster,
        PokerTablePresenceService $presence,
        PokerTableReadinessService $readiness,
        PokerBotTurnProcessor $botTurnProcessor,
    ): JsonResponse {
        $presence->markCurrentUserOnline($table, $request->user());

        abort_if(! $request->user(), 403, 'Você precisa estar autenticado para iniciar uma nova mão.');

        if (! $readiness->canStartHand($table)) {
            $waitingState = $readiness->waitingState($table);

            return response()->json([
                'state' => $privateState->forUser($table, $waitingState, $request->user()),
                'players' => $this->serializeRealPlayers($table),
                'seatSlots' => $this->serializeSeatSlots($table),
                'message' => $waitingState['waitingForPlayers']['message'] ?? 'A mesa ainda está aguardando jogadores.',
            ]);
        }

        $state = $pokerPersistence->currentStateForTable($table);

        if ($state && ! (bool) ($state['isFinished'] ?? false)) {
            return response()->json([
                'state' => $privateState->forUser($table, $state, $request->user()),
                'players' => $this->serializeRealPlayers($table),
                'seatSlots' => $this->serializeSeatSlots($table),
                'message' => 'Já existe uma mão em andamento nesta mesa.',
            ]);
        }

        $isBotVsBotSimulation = $botTurnProcessor->isBotVsBotTable($table);
        $nextState = $readiness->startExplicitNewHand($table, $pokerPersistence, $isBotVsBotSimulation);

        if (! (bool) data_get($nextState, 'multiSeat.enabled', false) && ! $isBotVsBotSimulation) {
            $nextState = $botTurnProcessor->process($table, $nextState);
        }

        $nextState = $pokerPersistence->persist($nextState);

        $broadcaster->broadcast($nextState);

        return response()->json([
            'state' => $privateState->forUser($table, $nextState, $request->user()),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'message' => 'Nova mão iniciada para todos os jogadores.',
        ]);
    }
}
