<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerBankrollService;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Support\Poker\SerializesPokerTablePlayers;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableRebuyController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        PokerBankrollService $bankroll,
        LocalPokerPersistenceService $pokerPersistence,
    ): JsonResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para fazer rebuy.');

        $validated = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        $player = $table->realPlayers()
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($pokerPersistence->currentStateForTable($table) !== null) {
            return response()->json([
                'message' => 'Rebuy disponível somente entre mãos. Aguarde a mão atual terminar.',
            ], 422);
        }

        try {
            $player = $bankroll->rebuy(
                table: $table,
                player: $player,
                amount: isset($validated['amount']) ? (int) $validated['amount'] : null,
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Rebuy realizado com sucesso.',
            'player' => $this->serializeTablePlayer($player),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
            'bankroll' => $user->fresh()->poker_bankroll,
        ]);
    }
}
