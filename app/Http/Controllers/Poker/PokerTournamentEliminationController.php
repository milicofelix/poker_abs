<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Services\Poker\PokerTournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class PokerTournamentEliminationController extends Controller
{
    public function __invoke(
        PokerTournament $tournament,
        PokerTournamentParticipant $participant,
        PokerTournamentService $tournaments,
    ): RedirectResponse {
        try {
            $tournaments->eliminate($tournament, $participant);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Eliminação registrada no torneio.');
    }
}
