<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Services\Poker\PokerTournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class PokerTournamentAddonController extends Controller
{
    public function __invoke(PokerTournament $tournament, PokerTournamentParticipant $participant, PokerTournamentService $service): RedirectResponse
    {
        try {
            $service->addOn($tournament, $participant);

            return redirect()
                ->route('poker.tournaments.index')
                ->with('success', 'Add-on registrado no torneio.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('poker.tournaments.index')
                ->with('error', $exception->getMessage());
        }
    }
}
