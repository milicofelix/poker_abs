<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerTablePlayController extends Controller
{
    public function __invoke(
        Request $request,
        PokerTable $table,
        StartPokerHandAction $startPokerHand,
        LocalPokerPersistenceService $pokerPersistence,
    ): Response {
        $hand = $request->boolean('new')
            ? null
            : $pokerPersistence->currentStateForTable($table);

        if (! $hand) {
            $hand = $pokerPersistence->startOnTable($table, $startPokerHand->execute());
        }

        $realPlayers = $table->realPlayers()
            ->orderBy('joined_at')
            ->get()
            ->map(static fn ($player): array => [
                'id' => $player->id,
                'userId' => $player->user_id,
                'nickname' => $player->nickname,
                'stack' => $player->stack,
                'status' => $player->status,
            ]);

        return Inertia::render('Poker/Play', [
            'hand' => $hand,
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
                'url' => route('poker.tables.show', $table),
                'newHandUrl' => route('poker.tables.show', ['table' => $table, 'new' => 1]),
                'stateUrl' => route('poker.tables.state', $table),
                'actionUrl' => route('poker.tables.actions', $table),
                'timeoutUrl' => route('poker.tables.timeout', $table),
                'joinUrl' => route('poker.tables.join', $table),
                'lobbyUrl' => route('poker.lobby'),
                'realPlayers' => $realPlayers,
            ],
        ]);
    }
}
