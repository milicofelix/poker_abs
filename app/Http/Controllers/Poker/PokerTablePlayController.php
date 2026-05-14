<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\MultiplayerPokerPrivateStateService;
use App\Services\Poker\PokerTablePresenceService;
use App\Support\Poker\SerializesPokerTablePlayers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerTablePlayController extends Controller
{
    use SerializesPokerTablePlayers;

    public function __invoke(
        Request $request,
        PokerTable $table,
        StartPokerHandAction $startPokerHand,
        LocalPokerPersistenceService $pokerPersistence,
        MultiplayerPokerPrivateStateService $privateState,
        PokerTablePresenceService $presence,
    ): Response {
        $presence->markCurrentUserOnline($table, $request->user());

        $hand = $request->boolean('new')
            ? null
            : $pokerPersistence->currentStateForTable($table);

        if (! $hand) {
            $hand = $pokerPersistence->startOnTable($table, $startPokerHand->execute());
        }

        $hand = $privateState->forUser($table, $hand, $request->user());

        return Inertia::render('Poker/Play', [
            'hand' => $hand,
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
                'url' => route('poker.tables.show', $table),
                'newHandUrl' => route('poker.tables.show', ['table' => $table, 'new' => 1]),
                'newHandActionUrl' => route('poker.tables.new-hand', $table),
                'stateUrl' => route('poker.tables.state', $table),
                'actionUrl' => route('poker.tables.actions', $table),
                'timeoutUrl' => route('poker.tables.timeout', $table),
                'joinUrl' => route('poker.tables.join', $table),
                'seatUrl' => route('poker.tables.seat', $table),
                'leaveUrl' => route('poker.tables.leave', $table),
                'maxPlayers' => $table->max_players,
                'lobbyUrl' => route('poker.lobby'),
                'realPlayers' => $this->serializeRealPlayers($table),
                'seatSlots' => $this->serializeSeatSlots($table),
                'currentUserId' => $request->user()?->id,
            ],
        ]);
    }
}
