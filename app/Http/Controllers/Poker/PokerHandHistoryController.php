<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerHand;
use Inertia\Inertia;
use Inertia\Response;

final class PokerHandHistoryController extends Controller
{
    public function __invoke(): Response
    {
        $hands = PokerHand::query()
            ->with([
                'table:id,name,status',
                'actions' => fn ($query) => $query
                    ->with('player:id,name,type')
                    ->oldest('acted_at')
                    ->oldest('id'),
            ])
            ->withCount('actions')
            ->latest('started_at')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (PokerHand $hand): array => $this->serializeHand($hand));

        return Inertia::render('Poker/History', [
            'hands' => $hands,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHand(PokerHand $hand): array
    {
        $lastAction = $hand->actions->last();

        return [
            'id' => $hand->id,
            'code' => $hand->code,
            'table' => $hand->table?->name ?? 'Mesa local',
            'status' => $hand->status,
            'statusLabel' => $hand->status === 'finished' ? 'Finalizada' : 'Em andamento',
            'street' => $hand->street,
            'streetLabel' => $this->streetLabel($hand->street),
            'pot' => $hand->pot,
            'currentBet' => $hand->current_bet,
            'winner' => $hand->winner ? [
                'player' => $hand->winner,
                'label' => $hand->winner_label,
                'handName' => $hand->winning_hand_name,
            ] : null,
            'actionsCount' => $hand->actions_count,
            'startedAt' => $hand->started_at?->format('d/m/Y H:i'),
            'finishedAt' => $hand->finished_at?->format('d/m/Y H:i'),
            'lastAction' => $lastAction ? [
                'player' => $lastAction->player?->name ?? 'Jogador',
                'action' => $lastAction->action,
                'amount' => $lastAction->amount,
                'potAfterAction' => $lastAction->pot_after_action,
                'message' => $lastAction->metadata['message'] ?? null,
            ] : null,
        ];
    }

    private function streetLabel(string $street): string
    {
        return match ($street) {
            'pre_flop' => 'Pré-flop',
            'flop' => 'Flop',
            'turn' => 'Turn',
            'river' => 'River',
            'showdown' => 'Showdown',
            default => ucfirst(str_replace('_', ' ', $street)),
        };
    }
}
