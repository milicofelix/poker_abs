<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use Inertia\Inertia;
use Inertia\Response;

final class PokerLobbyController extends Controller
{
    public function __invoke(): Response
    {
        $tables = PokerTable::query()
            ->withCount('realPlayers')
            ->with(['hands' => fn ($query) => $query->latest('id')->limit(1)])
            ->latest('id')
            ->get()
            ->map(static function (PokerTable $table): array {
                $latestHand = $table->hands->first();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'status' => $table->status,
                    'smallBlind' => $table->small_blind,
                    'bigBlind' => $table->big_blind,
                    'maxPlayers' => $table->max_players,
                    'playersCount' => $table->real_players_count,
                    'latestHand' => $latestHand ? [
                        'id' => $latestHand->id,
                        'status' => $latestHand->status,
                        'street' => $latestHand->street,
                        'pot' => $latestHand->pot,
                    ] : null,
                    'url' => route('poker.tables.show', $table),
                ];
            });

        return Inertia::render('Poker/Lobby', [
            'tables' => $tables,
        ]);
    }
}
