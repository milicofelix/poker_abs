<?php

namespace App\Http\Controllers\Poker;

use App\Http\Controllers\Controller;
use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use Inertia\Inertia;
use Inertia\Response;

final class PokerHandShowController extends Controller
{
    public function __invoke(PokerHand $hand): Response
    {
        $hand->load([
            'table.players' => fn ($query) => $query->orderBy('seat'),
            'actions' => fn ($query) => $query
                ->with('player:id,name,type,seat')
                ->oldest('acted_at')
                ->oldest('id'),
        ]);

        return Inertia::render('Poker/HandShow', [
            'hand' => $this->serializeHand($hand),
        ]);
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
            'players' => $hand->table?->players
                ->map(fn (PokerPlayer $player): array => [
                    'id' => $player->id,
                    'seat' => $player->seat,
                    'name' => $player->name,
                    'type' => $player->type,
                    'typeLabel' => $this->playerTypeLabel($player->type),
                    'stack' => $player->stack,
                    'isActive' => $player->is_active,
                ])
                ->values()
                ->all() ?? [],
            'actions' => $hand->actions
                ->map(fn (PokerActionLog $action): array => [
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
                ->values()
                ->all(),
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

    private function playerTypeLabel(string $type): string
    {
        return match ($type) {
            'local_user' => 'Jogador local',
            'simple_bot' => 'Bot simples',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
