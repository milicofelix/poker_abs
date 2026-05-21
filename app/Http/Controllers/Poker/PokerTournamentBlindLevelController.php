<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTournament;
use App\Services\Poker\PokerTournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class PokerTournamentBlindLevelController extends Controller
{
    public function __invoke(PokerTournament $tournament, PokerTournamentService $service): RedirectResponse
    {
        try {
            $service->advanceBlindLevel($tournament);

            return redirect()
                ->route('poker.tournaments.index')
                ->with('success', 'Nível de blinds avançado.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('poker.tournaments.index')
                ->with('error', $exception->getMessage());
        }
    }
}
