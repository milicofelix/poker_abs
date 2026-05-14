<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableJoinController extends Controller
{
    public function __invoke(
        Request $request,
        PokerTable $table,
        JoinPokerTableAction $joinPokerTable,
    ): JsonResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para entrar como jogador real.');

        $player = $joinPokerTable->execute($table, $user);

        $players = $table->realPlayers()
            ->with('user:id,name,email')
            ->orderBy('joined_at')
            ->get();

        return response()->json([
            'message' => 'Jogador entrou na mesa com sucesso.',
            'player' => [
                'id' => $player->id,
                'userId' => $player->user_id,
                'nickname' => $player->nickname,
                'stack' => $player->stack,
                'status' => $player->status,
            ],
            'players' => $players->map(static fn ($tablePlayer): array => [
                'id' => $tablePlayer->id,
                'userId' => $tablePlayer->user_id,
                'nickname' => $tablePlayer->nickname,
                'stack' => $tablePlayer->stack,
                'status' => $tablePlayer->status,
            ])->values(),
        ]);
    }
}
