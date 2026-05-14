<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\MultiplayerPokerTableStateBroadcaster;
use App\Services\Poker\PokerTurnTimeoutService;
use Illuminate\Http\JsonResponse;

final class PokerTableTurnTimeoutController extends Controller
{
    public function __invoke(
        PokerTable $table,
        PokerTurnTimeoutService $timeoutService,
        MultiplayerPokerTableStateBroadcaster $tableBroadcaster,
    ): JsonResponse {
        $result = $timeoutService->process($table);

        if ($result['processed']) {
            $tableBroadcaster->broadcast($result['state']);
        }

        return response()->json($result);
    }
}
