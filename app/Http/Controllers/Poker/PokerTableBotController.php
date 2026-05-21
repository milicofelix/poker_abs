<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\AddPokerBotToTableAction;
use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerBotTurnProcessor;
use App\Services\Poker\PokerTableReadinessService;
use App\Support\Poker\PokerBotProfiles;
use App\Support\Poker\SerializesPokerTablePlayers;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PokerTableBotController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        AddPokerBotToTableAction $addBot,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
        PokerBotTurnProcessor $botTurnProcessor,
        PokerTableReadinessService $readiness,
        StartPokerHandAction $startPokerHand,
    ): JsonResponse {
        abort_if(! $request->user(), 401, 'É necessário estar autenticado para adicionar bots.');

        $validated = $request->validate([
            'profile' => ['required', 'string', Rule::in(PokerBotProfiles::keys())],
            'difficulty' => ['nullable', 'string', Rule::in(PokerBotProfiles::difficulties())],
            'replace_bot' => ['nullable', 'boolean'],
        ]);

        if ((bool) ($validated['replace_bot'] ?? false)) {
            $runningState = $pokerPersistence->currentStateForTable($table);

            if ($runningState && ! (bool) ($runningState['isFinished'] ?? false)) {
                return response()->json([
                    'message' => 'Só é possível trocar o adversário após a mão finalizar.',
                ], 422);
            }
        }

        try {
            $bot = $addBot->execute(
                $table,
                (string) $validated['profile'],
                (string) ($validated['difficulty'] ?? 'normal'),
                (bool) ($validated['replace_bot'] ?? false),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $state = $readiness->startIfReady($table, $startPokerHand, $pokerPersistence);

        if (! (bool) ($state['isWaitingForPlayers'] ?? false)) {
            $isBotVsBotSimulation = $this->hasBotVsBotSimulation($table);
            $state['botVsBotSimulation'] = $isBotVsBotSimulation;

            if (! $isBotVsBotSimulation) {
                $state = $botTurnProcessor->process($table, $state);
            }

            $state = $pokerPersistence->persist($state);

            $tableBroadcaster->broadcast($state);
        }

        return response()->json([
            'message' => (bool) ($validated['replace_bot'] ?? false)
                ? sprintf('Adversário trocado para %s.', $bot->nickname)
                : sprintf('%s entrou na mesa.', $bot->nickname),
            'bot' => $this->serializeTablePlayer($bot),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'state' => $state ? $privateState->forUser($table, $state, $request->user()) : null,
        ]);
    }

    private function hasBotVsBotSimulation(PokerTable $table): bool
    {
        return $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->where('is_bot', true)
            ->count() >= 2;
    }
}
