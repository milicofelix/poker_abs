<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTournament;
use App\Services\Poker\PokerTournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class PokerTournamentFinalTableController extends Controller
{
    public function __invoke(PokerTournament $tournament, PokerTournamentService $service): RedirectResponse
    {
        try {
            $service->prepareFinalTable($tournament);

            return redirect()
                ->route('poker.tournaments.index')
                ->with('success', 'Mesa final organizada.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('poker.tournaments.index')
                ->with('error', $exception->getMessage());
        }
    }
}
