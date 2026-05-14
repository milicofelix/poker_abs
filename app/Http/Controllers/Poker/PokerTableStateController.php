<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PokerTableStateController extends Controller
{
    public function __invoke(
        Request $request,
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
    ): JsonResponse {
        $state = $pokerPersistence->currentStateForTable($table);

        abort_if(! $state, 404, 'Mesa sem mão ativa.');

        return response()->json([
            'state' => $privateState->forUser($table, $state, $request->user()),
        ]);
    }
}
