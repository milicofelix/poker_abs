<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Services\Poker\PokerTournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerTournamentController extends Controller
{
    public function index(Request $request, PokerTournamentService $tournaments): Response
    {
        return Inertia::render('Poker/Tournaments', [
            'tournamentCenter' => $tournaments->indexPayload($request->user()),
        ]);
    }

    public function store(Request $request, PokerTournamentService $tournaments): RedirectResponse
    {
        abort_if(! $request->user(), 401, 'É necessário estar autenticado para criar torneios.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'buy_in' => ['required', 'integer', 'min:100', 'max:100000'],
            'starting_stack' => ['required', 'integer', 'min:500', 'max:100000'],
            'max_players' => ['required', 'integer', 'min:2', 'max:200'],
            'starts_at' => ['nullable', 'date'],
        ]);

        $tournament = $tournaments->create($validated);

        return redirect()
            ->route('poker.tournaments.index')
            ->with('success', sprintf('Torneio %s criado com inscrições abertas.', $tournament->name));
    }
}
