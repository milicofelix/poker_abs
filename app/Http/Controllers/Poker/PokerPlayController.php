<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\LocalPokerSessionService;
use App\Services\Poker\PokerPhaseEightClosureService;
use App\Services\Poker\PokerPhaseNineActionButtonUxService;
use App\Services\Poker\PokerPhaseNineStateFeedbackService;
use App\Services\Poker\PokerPhaseNineVisualAuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerPlayController extends Controller
{
    public function __invoke(
        Request $request,
        StartPokerHandAction $startPokerHand,
        LocalPokerSessionService $pokerSession,
        LocalPokerPersistenceService $pokerPersistence,
        PokerPhaseEightClosureService $phaseEightClosure,
        PokerPhaseNineVisualAuditService $visualAudit,
        PokerPhaseNineActionButtonUxService $actionButtonUx,
        PokerPhaseNineStateFeedbackService $stateFeedback,
    ): Response {
        if ($request->boolean('new')) {
            $pokerSession->forget();
        }

        $hand = $pokerSession->current();

        if (! $hand) {
            $hand = $pokerPersistence->start($startPokerHand->execute());
            $pokerSession->store($hand);
        }

        $table = $this->localTablePayload($hand, $phaseEightClosure, $visualAudit, $actionButtonUx, $stateFeedback);

        return Inertia::render('Poker/Play', [
            'hand' => $hand,
            'table' => $table,
        ]);
    }

    /**
     * @param array<string, mixed> $hand
     * @return array<string, mixed>|null
     */
    private function localTablePayload(array $hand, PokerPhaseEightClosureService $phaseEightClosure, PokerPhaseNineVisualAuditService $visualAudit, PokerPhaseNineActionButtonUxService $actionButtonUx, PokerPhaseNineStateFeedbackService $stateFeedback): ?array
    {
        $tableId = $hand['persistence']['tableId'] ?? null;

        if (! $tableId) {
            return null;
        }

        /** @var PokerTable|null $table */
        $table = PokerTable::query()->find($tableId);

        if (! $table) {
            return null;
        }

        $phaseClosure = $phaseEightClosure->forLocalTable($table);

        return [
            'id' => $table->id,
            'name' => $table->name,
            'url' => route('poker.play'),
            'newHandUrl' => route('poker.play', ['new' => 1]),
            'actionUrl' => route('poker.actions'),
            'maxPlayers' => $table->max_players,
            'smallBlind' => $table->small_blind,
            'bigBlind' => $table->big_blind,
            'lobbyUrl' => route('poker.lobby'),
            'isLocalMode' => true,
            'modeLabel' => 'Mesa local',
            'modeDescription' => 'Engine local clássica para testes rápidos, histórico, ranking e estatísticas.',
            'reviewChecklist' => $phaseClosure['checklist'],
            'phaseClosure' => $phaseClosure,
            'visualAudit' => $visualAudit->forTable($table, true),
            'actionButtonUx' => $actionButtonUx->forTable($table, true),
            'stateFeedback' => $stateFeedback->forTable($table, true),
        ];
    }
}
