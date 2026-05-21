<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTournament;
use App\Services\Poker\PokerTournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class PokerTournamentStartController extends Controller
{
    public function __invoke(PokerTournament $tournament, PokerTournamentService $tournaments): RedirectResponse
    {
        try {
            $tournaments->start($tournament);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Torneio iniciado. Jogadores inscritos agora estão ativos.');
    }
}
