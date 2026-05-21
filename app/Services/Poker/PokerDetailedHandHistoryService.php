<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PokerDetailedHandHistoryService
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginated(array $filters): LengthAwarePaginator
    {
        $query = PokerHand::query()
            ->with([
                'table:id,name,buy_in,small_blind,big_blind',
                'actions' => fn ($query) => $query
                    ->with('player:id,name,type,seat')
                    ->oldest('acted_at')
                    ->oldest('id'),
            ])
            ->latest('created_at')
            ->latest('id');

        $this->applyFilters($query, $filters);

        return $query
            ->paginate(12)
            ->withQueryString()
            ->through(fn (PokerHand $hand): array => $this->summary($hand));
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(PokerHand $hand): array
    {
        $hand->loadMissing([
            'table.players' => fn ($query) => $query->orderBy('seat'),
            'table.realPlayers.user:id,name,email',
            'actions' => fn ($query) => $query
                ->with('player:id,name,type,seat')
                ->oldest('acted_at')
                ->oldest('id'),
        ]);

        $state = is_array($hand->state_payload) ? $hand->state_payload : [];

        return [
            ...$this->summary($hand),
            'winner' => $this->winnerPayload($hand, $state),
            'board' => $this->cards((array) ($state['communityCards'] ?? [])),
            'players' => $this->playersPayload($hand, $state),
            'actions' => $this->actionsPayload($hand->actions),
            'sidePots' => $this->sidePotsPayload($state),
            'streets' => $this->streetsPayload($hand->actions),
            'stateHighlights' => [
                'dealerSeat' => data_get($state, 'multiSeat.dealerSeat'),
                'smallBlindSeat' => data_get($state, 'multiSeat.smallBlindSeat'),
                'bigBlindSeat' => data_get($state, 'multiSeat.bigBlindSeat'),
                'showdownPhase' => data_get($state, 'multiSeat.showdownResolutionPhase'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(array $filters): array
    {
        return [
            'player' => $filters['player'] ?? '',
            'table_id' => $filters['table_id'] ?? '',
            'result' => $filters['result'] ?? 'all',
            'period' => $filters['period'] ?? 'all',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function tableOptions(): array
    {
        return \App\Models\Poker\PokerTable::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($table): array => [
                'id' => $table->id,
                'name' => $table->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int|string, name: string}>
     */
    public function playerOptions(): array
    {
        $realPlayers = PokerTablePlayer::query()
            ->with('user:id,name')
            ->whereNotNull('user_id')
            ->get()
            ->toBase()
            ->map(fn (PokerTablePlayer $player): array => [
                'id' => 'user:'.$player->user_id,
                'name' => $player->user?->name ?? $player->nickname ?? 'Jogador',
            ]);

        $localPlayers = PokerPlayer::query()
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->get()
            ->toBase()
            ->map(fn (PokerPlayer $player): array => [
                'id' => 'name:'.$player->name,
                'name' => $player->name,
            ]);

        return collect()
            ->concat($realPlayers)
            ->concat($localPlayers)
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param Builder<PokerHand> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['table_id'])) {
            $query->where('poker_table_id', (int) $filters['table_id']);
        }

        if (! empty($filters['player'])) {
            $needle = $this->normalizePlayerFilter((string) $filters['player']);

            $query->where(function (Builder $query) use ($needle): void {
                if (str_starts_with($needle, 'user:')) {
                    $userId = (int) str_replace('user:', '', $needle);

                    $query
                        ->whereJsonContains('state_payload->multiSeat->players', ['userId' => $userId])
                        ->orWhereJsonContains('state_payload->multiSeat->players', ['user_id' => $userId])
                        ->orWhereHas('table.realPlayers', fn (Builder $query) => $query->where('user_id', $userId));

                    return;
                }

                $name = str_replace('name:', '', $needle);

                $query
                    ->whereJsonContains('state_payload->players', ['name' => $name])
                    ->orWhereJsonContains('state_payload->multiSeat->players', ['name' => $name])
                    ->orWhereHas('table.players', fn (Builder $query) => $query->where('name', $name));
            });
        }

        if (($filters['result'] ?? 'all') === 'won' && ! empty($filters['player'])) {
            $winnerName = str_replace('name:', '', $this->normalizePlayerFilter((string) $filters['player']));
            $query->where(function (Builder $query) use ($winnerName): void {
                $query
                    ->where('winner_label', $winnerName)
                    ->orWhereJsonContains('state_payload->bankrollSettlement->winners', ['name' => $winnerName]);
            });
        }

        if (($filters['result'] ?? 'all') === 'lost' && ! empty($filters['player'])) {
            $winnerName = str_replace('name:', '', $this->normalizePlayerFilter((string) $filters['player']));
            $query->where(function (Builder $query) use ($winnerName): void {
                $query
                    ->where(function (Builder $query) use ($winnerName): void {
                        $query->whereNull('winner_label')->orWhere('winner_label', '!=', $winnerName);
                    })
                    ->whereJsonDoesntContain('state_payload->bankrollSettlement->winners', ['name' => $winnerName]);
            });
        }

        match ($filters['period'] ?? 'all') {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(PokerHand $hand): array
    {
        $state = is_array($hand->state_payload) ? $hand->state_payload : [];
        $lastAction = $hand->actions->last();

        return [
            'id' => $hand->id,
            'code' => $hand->code,
            'table' => $hand->table?->name ?? 'Mesa local',
            'tableId' => $hand->poker_table_id,
            'status' => $hand->status,
            'statusLabel' => $hand->status === 'finished' ? 'Finalizada' : 'Em andamento',
            'street' => $hand->street,
            'streetLabel' => $this->streetLabel($hand->street),
            'pot' => (int) $hand->pot,
            'currentBet' => (int) $hand->current_bet,
            'winnerLabel' => $hand->winner_label ?: $this->winnerLabelFromState($state),
            'winningHandName' => $hand->winning_hand_name,
            'playersCount' => $this->playersFromState($state)->count() ?: ($hand->table?->players?->count() ?? 0),
            'boardCount' => count((array) ($state['communityCards'] ?? [])),
            'hasSidePot' => (bool) data_get($state, 'bankrollSettlement.sidePots.hasSidePot', data_get($state, 'multiSeat.sidePots.hasSidePot', false)),
            'sidePotTotal' => (int) data_get($state, 'bankrollSettlement.sidePots.total', data_get($state, 'multiSeat.sidePots.total', 0)),
            'actionsCount' => $hand->actions->count(),
            'lastAction' => $lastAction ? $this->actionPayload($lastAction) : null,
            'startedAt' => $hand->started_at?->format('d/m/Y H:i'),
            'finishedAt' => $hand->finished_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function winnerPayload(PokerHand $hand, array $state): ?array
    {
        $winnerLabel = $hand->winner_label ?: $this->winnerLabelFromState($state);

        if (! $winnerLabel && ! $hand->winner) {
            return null;
        }

        return [
            'player' => $hand->winner,
            'label' => $winnerLabel ?: 'Vencedor não identificado',
            'handName' => $hand->winning_hand_name,
            'payouts' => data_get($state, 'bankrollSettlement.payoutsBySeat', []),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function playersPayload(PokerHand $hand, array $state): array
    {
        $playersFromState = $this->playersFromState($state);

        if ($playersFromState->isNotEmpty()) {
            return $playersFromState->map(fn (array $player): array => [
                'id' => $player['id'] ?? $player['userId'] ?? $player['user_id'] ?? $player['seat'] ?? null,
                'seat' => $player['seat'] ?? $player['seatNumber'] ?? $player['seat_number'] ?? null,
                'name' => $player['name'] ?? $player['nickname'] ?? 'Jogador',
                'type' => $player['type'] ?? (($player['isBot'] ?? $player['is_bot'] ?? false) ? 'bot' : 'real'),
                'typeLabel' => $this->playerTypeLabel((string) ($player['type'] ?? (($player['isBot'] ?? false) ? 'bot' : 'real'))),
                'stack' => (int) ($player['stack'] ?? 0),
                'contribution' => (int) ($player['contribution'] ?? $player['committed'] ?? $player['totalContribution'] ?? 0),
                'isAllIn' => (bool) ($player['isAllIn'] ?? $player['allIn'] ?? false),
                'isActive' => (bool) ($player['isActive'] ?? $player['active'] ?? true),
                'cards' => $this->cards((array) ($player['cards'] ?? $player['holeCards'] ?? [])),
            ])->values()->all();
        }

        return $hand->table?->players
            ->map(fn (PokerPlayer $player): array => [
                'id' => $player->id,
                'seat' => $player->seat,
                'name' => $player->name,
                'type' => $player->type,
                'typeLabel' => $this->playerTypeLabel($player->type),
                'stack' => $player->stack,
                'contribution' => 0,
                'isAllIn' => false,
                'isActive' => $player->is_active,
                'cards' => [],
            ])
            ->values()
            ->all() ?? [];
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array<string, mixed>>
     */
    private function actionsPayload(Collection $actions): array
    {
        return $actions
            ->map(fn (PokerActionLog $action): array => $this->actionPayload($action))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function actionPayload(PokerActionLog $action): array
    {
        return [
            'id' => $action->id,
            'street' => $action->street,
            'streetLabel' => $this->streetLabel($action->street),
            'player' => $action->player?->name ?? 'Jogador',
            'playerType' => $action->player?->type,
            'action' => $action->action,
            'amount' => (int) $action->amount,
            'potAfterAction' => (int) $action->pot_after_action,
            'message' => $action->metadata['message'] ?? null,
            'playerStack' => $action->metadata['player_stack'] ?? null,
            'opponentStack' => $action->metadata['opponent_stack'] ?? null,
            'actedAt' => $action->acted_at?->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sidePotsPayload(array $state): array
    {
        $payload = (array) data_get($state, 'bankrollSettlement.sidePots', data_get($state, 'multiSeat.sidePots', []));

        return [
            'hasSidePot' => (bool) ($payload['hasSidePot'] ?? false),
            'total' => (int) ($payload['total'] ?? 0),
            'allInLevels' => array_values((array) ($payload['allInLevels'] ?? [])),
            'pots' => array_values((array) ($payload['pots'] ?? [])),
        ];
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array{street: string, streetLabel: string, actionsCount: int, potAfterStreet: int}>
     */
    private function streetsPayload(Collection $actions): array
    {
        return $actions
            ->groupBy('street')
            ->map(fn (Collection $items, string $street): array => [
                'street' => $street,
                'streetLabel' => $this->streetLabel($street),
                'actionsCount' => $items->count(),
                'potAfterStreet' => (int) ($items->last()?->pot_after_action ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     * @return array<int, array<string, string>>
     */
    private function cards(array $cards): array
    {
        return collect($cards)
            ->map(fn (array $card): array => [
                'rank' => (string) ($card['rank'] ?? ''),
                'suit' => (string) ($card['suit'] ?? ''),
                'label' => (string) ($card['label'] ?? trim(($card['rank'] ?? '').($card['suit'] ?? ''))),
            ])
            ->filter(fn (array $card): bool => $card['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function playersFromState(array $state): Collection
    {
        $players = data_get($state, 'multiSeat.players', $state['players'] ?? []);

        return collect(is_array($players) ? $players : [])
            ->filter(fn ($player): bool => is_array($player))
            ->values();
    }

    private function winnerLabelFromState(array $state): ?string
    {
        $winner = data_get($state, 'winner.label')
            ?? data_get($state, 'conclusion.winnerLabel')
            ?? data_get($state, 'bankrollSettlement.winners.0.name');

        return is_string($winner) && $winner !== '' ? $winner : null;
    }

    private function normalizePlayerFilter(string $value): string
    {
        return str_contains($value, ':') ? $value : 'name:'.$value;
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
            'simple_bot', 'bot' => 'Bot',
            'real' => 'Jogador real',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
