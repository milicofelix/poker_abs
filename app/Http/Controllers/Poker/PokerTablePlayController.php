<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\PokerPhaseEightClosureService;
use App\Services\Poker\PokerPhaseNineActionButtonUxService;
use App\Services\Poker\PokerPhaseNineStateFeedbackService;
use App\Services\Poker\PokerPhaseNineMotionUxService;
use App\Services\Poker\PokerPhaseNineResponsiveUxService;
use App\Services\Poker\PokerPhaseNineFinalPolishService;
use App\Services\Poker\PokerPhaseNineVisualAuditService;
use App\Services\Poker\PokerPhaseTenHeadsUpAuditService;
use App\Services\Poker\PokerPhaseThirteenDesignAuditService;
use App\Services\Poker\PokerTablePresenceService;
use App\Services\Poker\PokerTableReadinessService;
use App\Services\Poker\PokerTableStateContractService;
use App\Support\Poker\PokerBotProfiles;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerTablePlayController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        StartPokerHandAction $startPokerHand,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        PokerTablePresenceService $presence,
        PokerTableReadinessService $readiness,
        PokerPhaseEightClosureService $phaseEightClosure,
        PokerPhaseNineVisualAuditService $visualAudit,
        PokerPhaseNineActionButtonUxService $actionButtonUx,
        PokerPhaseNineStateFeedbackService $stateFeedback,
        PokerPhaseNineMotionUxService $motionUx,
        PokerPhaseNineResponsiveUxService $responsiveUx,
        PokerPhaseNineFinalPolishService $finalPolish,
        PokerPhaseTenHeadsUpAuditService $phaseTenAudit,
        PokerPhaseThirteenDesignAuditService $phaseThirteenDesignAudit,
        PokerTableStateContractService $stateContracts,
    ): Response {
        $presence->markCurrentUserOnline($table, $request->user());

        $hand = $request->boolean('new')
            ? null
            : $pokerPersistence->currentStateForTable($table);

        if (! $hand) {
            $hand = $readiness->waitingState($table);
        }

        $hand = $privateState->forUser($table, $hand, $request->user());
        $phaseClosure = $phaseEightClosure->forLobbyTable($table);
        $stateContractPayload = $stateContracts->forTable($table, $hand ?? []);

        return Inertia::render('Poker/Play', [
            'hand' => $hand,
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
                'url' => route('poker.tables.show', $table),
                'newHandUrl' => route('poker.tables.show', ['table' => $table, 'new' => 1]),
                'newHandActionUrl' => route('poker.tables.new-hand', $table),
                'stateUrl' => route('poker.tables.state', $table),
                'actionUrl' => route('poker.tables.actions', $table),
                'timeoutUrl' => route('poker.tables.timeout', $table),
                'joinUrl' => route('poker.tables.join', $table),
                'seatUrl' => route('poker.tables.seat', $table),
                'leaveUrl' => route('poker.tables.leave', $table),
                'rebuyUrl' => route('poker.tables.rebuy', $table),
                'botUrl' => route('poker.tables.bots', $table),
                'maxPlayers' => $table->max_players,
                'isPrivate' => (bool) $table->is_private,
                'inviteCode' => $table->invite_code,
                'inviteUrl' => $table->invite_code ? route('poker.private-tables.invite', $table->invite_code) : null,
                'lobbyUrl' => route('poker.lobby'),
                'isLocalMode' => false,
                'modeLabel' => 'Mesa do lobby',
                'modeDescription' => 'Mesa multiplayer com assentos, presença, bots trocáveis, tempo real e timeout automático.',
                'reviewChecklist' => $phaseClosure['checklist'],
                'phaseClosure' => $phaseClosure,
                'visualAudit' => $visualAudit->forTable($table, false),
                'actionButtonUx' => $actionButtonUx->forTable($table, false),
                'stateFeedback' => $stateFeedback->forTable($table, false),
                'motionUx' => $motionUx->forTable($table, false),
                'responsiveUx' => $responsiveUx->forTable($table, false),
                'finalPolish' => $finalPolish->forTable($table, false),
                'phaseTenAudit' => $phaseTenAudit->forTable($table, false),
                'phaseThirteenDesignAudit' => $phaseThirteenDesignAudit->forTable($table, false),
                'stateContracts' => $stateContractPayload,
                'realPlayers' => $this->serializeRealPlayers($table),
                'seatSlots' => $this->serializeSeatSlots($table),
                'currentUserId' => $request->user()?->id,
                'currentUserBankroll' => $request->user()?->poker_bankroll,
                'defaultBuyIn' => $table->buyInAmount(),
                'buyIn' => $table->buyInAmount(),
                'minBuyIn' => PokerTable::MIN_BUY_IN,
                'maxBuyIn' => PokerTable::MAX_BUY_IN,
                'botProfiles' => array_values(PokerBotProfiles::all()),
                'botDifficulties' => PokerBotProfiles::difficulties(),
                'botDifficultyOptions' => array_values(PokerBotProfiles::difficultyOptions()),
            ],
        ]);
    }
}
