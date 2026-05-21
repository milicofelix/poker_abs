<?php

namespace App\Http\Controllers\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Support\Poker\SerializesPokerTablePlayers;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PokerTableJoinController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        JoinPokerTableAction $joinPokerTable,
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        abort_if(! $user, 401, 'É necessário estar autenticado para entrar como jogador real.');

        try {
            $player = $joinPokerTable->execute($table, $user);
        } catch (DomainException $exception) {
            if (! $request->expectsJson()) {
                return back()->with('error', $exception->getMessage());
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route('poker.tables.show', $table)
                ->with('success', 'Você entrou na mesa. Escolha um assento para começar.');
        }

        return response()->json([
            'message' => 'Jogador entrou na mesa com sucesso.',
            'player' => $this->serializeTablePlayer($player),
            'players' => $this->serializeRealPlayers($table),
            'seatSlots' => $this->serializeSeatSlots($table),
        ]);
    }
}
