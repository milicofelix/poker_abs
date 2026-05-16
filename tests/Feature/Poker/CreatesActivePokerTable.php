<?php

namespace Tests\Feature\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;

trait CreatesActivePokerTable
{
    protected function createActivePokerTable(array $attributes = []): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => $attributes['name'] ?? 'Mesa teste ativa',
            'status' => $attributes['status'] ?? 'waiting',
            'small_blind' => $attributes['small_blind'] ?? 10,
            'big_blind' => $attributes['big_blind'] ?? 20,
            'max_players' => $attributes['max_players'] ?? 2,
        ]);

        app(LocalPokerPersistenceService::class)->startOnTable(
            $table,
            app(StartPokerHandAction::class)->execute(),
        );

        return $table->fresh();
    }
}
