<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\SitPokerTablePlayerAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Support\Poker\SerializesPokerTablePlayers;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableSeatController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        SitPokerTablePlayerAction $sitPokerTablePlayer,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
    ): JsonResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado.');

        $validated = $request->validate([
            'seat_number' => ['required', 'integer'],
        ]);

        $player = $table->realPlayers()
            ->where('user_id', $user->id)
            ->firstOrFail();

        try {
            $player = $sitPokerTablePlayer->execute(
                $table,
                $player,
                (int) $validated['seat_number'],
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $state = $pokerPersistence->currentStateForTable($table);

        if ($state) {
            $tableBroadcaster->broadcast($state);
        }

        return response()->json([
            'message' => 'Assento escolhido com sucesso.',
            'player' => [
                'id' => $player->id,
                'userId' => $player->user_id,
                'nickname' => $player->nickname,
                'stack' => $player->stack,
                'status' => $player->status,
                'seatNumber' => $player->seat_number,
                'lastSeenAt' => $player->last_seen_at?->toIso8601String(),
            ],
            'players' => $this->serializeRealPlayers($table),
            'seats' => collect($this->serializeRealPlayers($table))
                ->filter(static fn (array $player): bool => $player['seatNumber'] !== null)
                ->values()
                ->all(),
            'seatSlots' => $this->serializeSeatSlots($table),
            'state' => $state ? $privateState->forUser($table, $state, $request->user()) : null,
        ]);
    }
}
