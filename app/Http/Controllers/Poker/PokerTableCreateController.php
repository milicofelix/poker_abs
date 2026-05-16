<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use Illuminate\Http\RedirectResponse;

final class PokerTableCreateController extends Controller
{
    public function __invoke(): RedirectResponse {
        $table = PokerTable::create([
            'name' => 'Mesa #'.(PokerTable::query()->count() + 1),
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        return redirect()->route('poker.tables.show', $table);
    }
}
