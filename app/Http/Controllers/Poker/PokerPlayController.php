<?php

namespace App\Http\Controllers\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTable;
use App\Services\Poker\LocalPokerPersistenceService;
use App\Services\Poker\LocalPokerSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerPlayController extends Controller
{
    public function __invoke(
        Request $request,
        StartPokerHandAction $startPokerHand,
        LocalPokerSessionService $pokerSession,
        LocalPokerPersistenceService $pokerPersistence,
    ): Response {
        if ($request->boolean('new')) {
            $pokerSession->forget();
        }

        $hand = $pokerSession->current();

        if (! $hand) {
            $hand = $pokerPersistence->start($startPokerHand->execute());
            $pokerSession->store($hand);
        }

        $table = $this->localTablePayload($hand);

        return Inertia::render('Poker/Play', [
            'hand' => $hand,
            'table' => $table,
        ]);
    }

    /**
     * @param array<string, mixed> $hand
     * @return array<string, mixed>|null
     */
    private function localTablePayload(array $hand): ?array
    {
        $tableId = $hand['persistence']['tableId'] ?? null;

        if (! $tableId) {
            return null;
        }

        /** @var PokerTable|null $table */
        $table = PokerTable::query()->find($tableId);

        if (! $table) {
            return null;
        }

        return [
            'id' => $table->id,
            'name' => $table->name,
            'url' => route('poker.play'),
            'newHandUrl' => route('poker.play', ['new' => 1]),
            'actionUrl' => route('poker.actions'),
            'maxPlayers' => $table->max_players,
            'smallBlind' => $table->small_blind,
            'bigBlind' => $table->big_blind,
            'lobbyUrl' => route('poker.lobby'),
            'isLocalMode' => true,
        ];
    }
}
