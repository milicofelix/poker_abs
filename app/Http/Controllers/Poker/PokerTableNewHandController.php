<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerTableReadinessService;
use App\Services\Poker\PokerBotTurnProcessor;
use App\Services\Poker\PokerTournamentRuntimeSyncService;
use App\Services\Poker\PokerTournamentTableBridgeService;
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
        PokerTournamentRuntimeSyncService $tournamentRuntimeSync,
        PokerTournamentTableBridgeService $tournamentBridge,
    ): JsonResponse {
        $presence->markCurrentUserOnline($table, $request->user());

        abort_if(! $request->user(), 403, 'Você precisa estar autenticado para iniciar uma nova mão.');

        $runningTournament = $tournamentRuntimeSync->runningTournamentForTable($table);

        if ($runningTournament) {
            $pokerPersistence->closeTerminalRunningHandsForTable($table);

            $latestState = $pokerPersistence->latestStateForTable($table);

            if ($latestState && (bool) ($latestState['isFinished'] ?? false)) {
                $runningTournament = $tournamentRuntimeSync->syncAfterStateChange($table, $latestState)
                    ?? $tournamentRuntimeSync->runningTournamentForTable($table);
            }

            if ($runningTournament && $runningTournament->status === PokerTournament::STATUS_RUNNING) {
                $preparedTable = $tournamentBridge->prepareRuntimeTableForNextHand($runningTournament);
                $table = $preparedTable ?? $table->fresh();
            }
        }

        if ($runningTournament) {
            $pokerPersistence->closeTerminalRunningHandsForTable($table);
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

        if (! $readiness->canStartHand($table) && $runningTournament) {
            $freshTournament = $runningTournament->fresh(['participants.user', 'runtimeTable']);
            $activeParticipants = $freshTournament->participants
                ->filter(static fn ($participant): bool => $participant->status === PokerTournamentParticipant::STATUS_ACTIVE && (int) $participant->current_stack > 0)
                ->count();

            if ($activeParticipants >= 2 && $freshTournament->status === PokerTournament::STATUS_RUNNING) {
                $preparedTable = $tournamentBridge->prepareRuntimeTableForNextHand($freshTournament);
                $table = ($preparedTable ?? $table)->fresh(['realPlayers.user', 'hands']);
                $pokerPersistence->closeTerminalRunningHandsForTable($table);
            }
        }

        if (! $readiness->canStartHand($table)) {
            $waitingState = $readiness->waitingState($table);

            return response()->json([
                'state' => $privateState->forUser($table, $waitingState, $request->user()),
                'players' => $this->serializeRealPlayers($table),
                'seatSlots' => $this->serializeSeatSlots($table),
                'message' => $waitingState['waitingForPlayers']['message'] ?? 'A mesa ainda está aguardando jogadores.',
            ]);
        }

        $isBotVsBotSimulation = $botTurnProcessor->isBotVsBotTable($table);
        $nextState = $readiness->startExplicitNewHand($table, $pokerPersistence, $isBotVsBotSimulation);

        if ((bool) ($nextState['isWaitingForPlayers'] ?? false)) {
            return response()->json([
                'state' => $privateState->forUser($table, $nextState, $request->user()),
                'players' => $this->serializeRealPlayers($table),
                'seatSlots' => $this->serializeSeatSlots($table),
                'message' => $nextState['waitingForPlayers']['message'] ?? 'A mesa ainda está aguardando jogadores.',
            ]);
        }

        if (! (bool) data_get($nextState, 'multiSeat.enabled', false) && ! $isBotVsBotSimulation) {
            $nextState = $botTurnProcessor->process($table, $nextState);
        }

        $nextState = $pokerPersistence->persist($nextState);
        $tournamentRuntimeSync->syncAfterStateChange($table, $nextState);

        $broadcaster->broadcast($nextState);

        return response()->json([
            'state' => $privateState->forUser($table, $nextState, $request->user()),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'message' => 'Nova mão iniciada para todos os jogadores.',
        ]);
    }
}
