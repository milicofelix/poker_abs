<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use Illuminate\Support\Collection;

final class PokerStatisticsService
{
    /**
     * @return array<string, mixed>
     */
    public function localStatistics(): array
    {
        $finishedHands = PokerHand::query()
            ->where('status', 'finished')
            ->latest('finished_at')
            ->get(['id', 'winner', 'winner_label', 'winning_hand_name', 'pot', 'finished_at']);

        $actions = PokerActionLog::query()
            ->with('player:id,name,type')
            ->oldest('acted_at')
            ->oldest('id')
            ->get(['id', 'poker_hand_id', 'poker_player_id', 'street', 'action', 'amount', 'pot_after_action', 'acted_at']);

        return [
            'overview' => $this->overview($finishedHands, $actions),
            'players' => $this->players($finishedHands, $actions),
            'actions' => $this->actions($actions),
            'streets' => $this->streets($actions),
        ];
    }

    /**
     * @param Collection<int, PokerHand> $hands
     * @param Collection<int, PokerActionLog> $actions
     * @return array<string, mixed>
     */
    private function overview(Collection $hands, Collection $actions): array
    {
        $totalHands = $hands->count();
        $handsWithWinner = $hands->whereNotNull('winner');
        $ties = $handsWithWinner->where('winner', 'tie')->count();
        $showdowns = $hands->filter(fn (PokerHand $hand): bool => $hand->street === 'showdown' || filled($hand->winning_hand_name))->count();

        return [
            'handsPlayed' => $totalHands,
            'totalActions' => $actions->count(),
            'totalPot' => $hands->sum('pot'),
            'averagePot' => $totalHands > 0 ? round($hands->sum('pot') / $totalHands, 2) : 0,
            'showdowns' => $showdowns,
            'showdownRate' => $this->percentage($showdowns, $totalHands),
            'ties' => $ties,
            'lastFinishedAt' => $hands->first()?->finished_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * @param Collection<int, PokerHand> $hands
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array<string, mixed>>
     */
    private function players(Collection $hands, Collection $actions): array
    {
        $playerKeys = collect(['player', 'opponent']);

        return $playerKeys
            ->map(function (string $key) use ($hands, $actions): array {
                $playerActions = $actions->filter(fn (PokerActionLog $action): bool => $this->playerKey($action) === $key);
                $victories = $hands->where('winner', $key)->count();
                $handsPlayed = $hands->count();

                return [
                    'player' => $key,
                    'label' => $this->playerLabel($key),
                    'handsPlayed' => $handsPlayed,
                    'victories' => $victories,
                    'winRate' => $this->percentage($victories, $handsPlayed),
                    'actions' => $playerActions->count(),
                    'checks' => $this->countAction($playerActions, 'Check'),
                    'calls' => $this->countAction($playerActions, 'Call'),
                    'raises' => $this->countAction($playerActions, 'Raise'),
                    'folds' => $this->countAction($playerActions, 'Fold'),
                    'aggressiveActions' => $this->countAction($playerActions, 'Raise'),
                    'chipsInvested' => $playerActions->sum('amount'),
                    'lastActionAt' => $playerActions->last()?->acted_at?->format('d/m/Y H:i'),
                ];
            })
            ->all();
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<string, int>
     */
    private function actions(Collection $actions): array
    {
        return [
            'checks' => $this->countAction($actions, 'Check'),
            'calls' => $this->countAction($actions, 'Call'),
            'raises' => $this->countAction($actions, 'Raise'),
            'folds' => $this->countAction($actions, 'Fold'),
        ];
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     * @return array<int, array<string, mixed>>
     */
    private function streets(Collection $actions): array
    {
        return collect(['preflop', 'flop', 'turn', 'river', 'showdown'])
            ->map(fn (string $street): array => [
                'street' => $street,
                'label' => $this->streetLabel($street),
                'actions' => $actions->where('street', $street)->count(),
                'chipsInvested' => $actions->where('street', $street)->sum('amount'),
            ])
            ->filter(fn (array $street): bool => $street['actions'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, PokerActionLog> $actions
     */
    private function countAction(Collection $actions, string $action): int
    {
        return $actions->where('action', $action)->count();
    }

    private function playerKey(PokerActionLog $action): string
    {
        return match ($action->player?->type) {
            'human', 'local_user' => 'player',
            'bot', 'simple_bot' => 'opponent',
            default => 'unknown',
        };
    }

    private function playerLabel(string $key): string
    {
        return match ($key) {
            'player' => 'Você',
            'opponent' => 'Oponente',
            default => 'Desconhecido',
        };
    }

    private function streetLabel(string $street): string
    {
        return match ($street) {
            'preflop' => 'Pré-flop',
            'flop' => 'Flop',
            'turn' => 'Turn',
            'river' => 'River',
            'showdown' => 'Showdown',
            default => ucfirst($street),
        };
    }

    private function percentage(int $value, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }
}
