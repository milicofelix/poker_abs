<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\SitPokerTablePlayerAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableSeatController extends Controller
{
    public function __invoke(
        Request $request,
        PokerTable $table,
        SitPokerTablePlayerAction $sitPokerTablePlayer,
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

        $seats = $table->realPlayers()
            ->orderByRaw('seat_number IS NULL')
            ->orderBy('seat_number')
            ->get()
            ->map(static fn ($tablePlayer): array => [
                'nickname' => $tablePlayer->nickname,
                'seatNumber' => $tablePlayer->seat_number,
                'status' => $tablePlayer->status,
            ])
            ->values();

        return response()->json([
            'message' => 'Jogador sentado com sucesso.',
            'player' => [
                'nickname' => $player->nickname,
                'seatNumber' => $player->seat_number,
            ],
            'seats' => $seats,
        ]);
    }
}
