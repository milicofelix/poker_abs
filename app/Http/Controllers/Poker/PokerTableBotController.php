<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\AddPokerBotToTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerBotTurnProcessor;
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
    ): JsonResponse {
        abort_if(! $request->user(), 401, 'É necessário estar autenticado para adicionar bots.');

        $validated = $request->validate([
            'profile' => ['required', 'string', Rule::in(PokerBotProfiles::keys())],
            'difficulty' => ['nullable', 'string', Rule::in(PokerBotProfiles::difficulties())],
        ]);

        try {
            $bot = $addBot->execute(
                $table,
                (string) $validated['profile'],
                (string) ($validated['difficulty'] ?? 'normal'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $state = $pokerPersistence->currentStateForTable($table);

        if ($state) {
            $state = $botTurnProcessor->process($table, $state);
            $state = $pokerPersistence->persist($state);

            $tableBroadcaster->broadcast($state);
        }

        return response()->json([
            'message' => sprintf('%s entrou na mesa.', $bot->nickname),
            'bot' => $this->serializeTablePlayer($bot),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'state' => $state ? $privateState->forUser($table, $state, $request->user()) : null,
        ]);
    }
}
