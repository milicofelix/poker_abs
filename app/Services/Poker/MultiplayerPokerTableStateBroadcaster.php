<?php

namespace App\Services\Poker;

use App\Events\Poker\PokerTableStateUpdated;

final class MultiplayerPokerTableStateBroadcaster
{
    /**
     * @param array<string, mixed> $state
     */
    public function broadcast(array $state): void
    {
        $tableId = (int) data_get($state, 'persistence.tableId', 0);

        if ($tableId <= 0) {
            return;
        }

        PokerTableStateUpdated::dispatch($tableId, $state);
    }
}
