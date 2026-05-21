<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerTournament;
use App\Services\Poker\PokerTournamentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PokerTournamentRegistrationController extends Controller
{
    public function __invoke(Request $request, PokerTournament $tournament, PokerTournamentService $tournaments): RedirectResponse
    {
        abort_if(! $request->user(), 401, 'É necessário estar autenticado para se inscrever.');

        try {
            $tournaments->register($tournament, $request->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Inscrição confirmada no torneio.');
    }
}
