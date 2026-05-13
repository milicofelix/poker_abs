<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerHand;
use Illuminate\Support\Collection;

final class PokerRankingService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function localRanking(): array
    {
        $finishedHands = PokerHand::query()
            ->where('status', 'finished')
            ->whereNotNull('winner')
            ->latest('finished_at')
            ->get(['id', 'winner', 'winner_label', 'winning_hand_name', 'pot', 'finished_at']);

        /** @var Collection<string, Collection<int, PokerHand>> $grouped */
        $grouped = $finishedHands->groupBy(fn (PokerHand $hand): string => (string) $hand->winner);

        return $grouped
            ->map(fn (Collection $hands, string $winner): array => $this->serializeRankingRow($winner, $hands))
            ->sort(fn (array $first, array $second): int => [
                $second['victories'],
                $second['chipsWon'],
                $first['label'],
            ] <=> [
                $first['victories'],
                $first['chipsWon'],
                $second['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, PokerHand> $hands
     * @return array<string, mixed>
     */
    private function serializeRankingRow(string $winner, Collection $hands): array
    {
        $lastHand = $hands->first();
        $label = $lastHand?->winner_label ?? $this->fallbackLabel($winner);
        $victories = $winner === 'tie' ? 0 : $hands->count();

        return [
            'winner' => $winner,
            'label' => $label,
            'victories' => $victories,
            'ties' => $winner === 'tie' ? $hands->count() : 0,
            'hands' => $hands->count(),
            'chipsWon' => $hands->sum('pot'),
            'lastWinningHand' => $lastHand?->winning_hand_name,
            'lastFinishedAt' => $lastHand?->finished_at?->format('d/m/Y H:i'),
        ];
    }

    private function fallbackLabel(string $winner): string
    {
        return match ($winner) {
            'player' => 'Você',
            'opponent' => 'Oponente',
            'tie' => 'Empates',
            default => ucfirst($winner),
        };
    }
}
