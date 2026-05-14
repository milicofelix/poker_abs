<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use Illuminate\Http\RedirectResponse;

final class PokerTableCreateController extends Controller
{
    public function __invoke(
        StartPokerHandAction $startPokerHand,
        LocalPokerPersistenceService $pokerPersistence,
    ): RedirectResponse {
        $table = PokerTable::create([
            'name' => 'Mesa #'.(PokerTable::query()->count() + 1),
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $pokerPersistence->startOnTable($table, $startPokerHand->execute());

        return redirect()->route('poker.tables.show', $table);
    }
}
