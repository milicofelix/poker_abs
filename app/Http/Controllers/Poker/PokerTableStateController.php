<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use Illuminate\Http\JsonResponse;

final class PokerTableStateController extends Controller
{
    public function __invoke(
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
    ): JsonResponse {
        $state = $pokerPersistence->currentStateForTable($table);

        abort_if(! $state, 404, 'Mesa sem mão ativa.');

        return response()->json([
            'state' => $state,
        ]);
    }
}
