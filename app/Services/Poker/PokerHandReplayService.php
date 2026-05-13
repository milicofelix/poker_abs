<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use Illuminate\Support\Collection;

final class PokerHandReplayService
{
    /**
     * @return array<string, mixed>
     */
    public function build(PokerHand $hand): array
    {
        $hand->loadMissing([
            'table.players' => fn ($query) => $query->orderBy('seat'),
            'actions' => fn ($query) => $query
                ->with('player:id,name,type,seat')
                ->oldest('acted_at')
                ->oldest('id'),
        ]);

        return [
            'hand' => $this->serializeHand($hand),
            'steps' => $this->steps($hand->actions),
            'streets' => $this->streets($hand->actions),
            'summary' => [
                'totalActions' => $hand->actions->count(),
                'finalPot' => $hand->pot,
                'winnerLabel' => $hand->winner_label,
                'winningHandName' => $hand->winning_hand_name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHand(PokerHand $hand): array
    {
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
            'startedAt' => $hand->started_at?->format('d/m/Y H:i'),
            'finishedAt' => $hand->finished_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array<string, mixed>>
     */
    private function steps(Collection $actions): array
    {
        return $actions
            ->values()
            ->map(fn (PokerActionLog $action, int $index): array => [
                'number' => $index + 1,
                'id' => $action->id,
                'street' => $action->street,
                'streetLabel' => $this->streetLabel($action->street),
                'player' => $action->player?->name ?? 'Jogador',
                'playerType' => $action->player?->type,
                'action' => $action->action,
                'amount' => $action->amount,
                'potAfterAction' => $action->pot_after_action,
                'message' => $action->metadata['message'] ?? null,
                'playerStack' => $action->metadata['player_stack'] ?? null,
                'opponentStack' => $action->metadata['opponent_stack'] ?? null,
                'actedAt' => $action->acted_at?->format('d/m/Y H:i:s'),
            ])
            ->all();
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array<string, mixed>>
     */
    private function streets(Collection $actions): array
    {
        return $actions
            ->groupBy('street')
            ->map(fn (Collection $streetActions, string $street): array => [
                'street' => $street,
                'label' => $this->streetLabel($street),
                'actionsCount' => $streetActions->count(),
                'potAfterStreet' => $streetActions->last()?->pot_after_action ?? 0,
            ])
            ->values()
            ->all();
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
