<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Services\Poker\PokerDetailedHandHistoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PokerHandHistoryController extends Controller
{
    public function __construct(
        private readonly PokerDetailedHandHistoryService $history,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'player' => ['nullable', 'string', 'max:120'],
            'table_id' => ['nullable', 'integer', 'exists:poker_tables,id'],
            'result' => ['nullable', 'string', 'in:all,won,lost'],
            'period' => ['nullable', 'string', 'in:all,today,7d,30d'],
        ]);

        return Inertia::render('Poker/History', [
            'hands' => $this->history->paginated($filters),
            'filters' => $this->history->filters($filters),
            'tables' => $this->history->tableOptions(),
            'players' => $this->history->playerOptions(),
        ]);
    }
}
