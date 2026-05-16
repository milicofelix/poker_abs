<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerBotTurnProcessor;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerTableReadinessService;
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
        PokerBotTurnProcessor $botTurnProcessor,
        PokerTablePresenceService $presence,
        PokerTableReadinessService $readiness,
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

        $nextState = $pokerPersistence->startOnTable($table, $startPokerHand->execute());
        $nextState = $botTurnProcessor->process($table, $nextState);
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
