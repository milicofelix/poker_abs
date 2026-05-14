<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableJoinController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        JoinPokerTableAction $joinPokerTable,
    ): JsonResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para entrar como jogador real.');

        $player = $joinPokerTable->execute($table, $user);

        return response()->json([
            'message' => 'Jogador entrou na mesa com sucesso.',
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
            'seatSlots' => $this->serializeSeatSlots($table),
        ]);
    }
}
