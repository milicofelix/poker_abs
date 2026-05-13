<?php

namespace App\Services\Poker;

final class ResolveLocalShowdownWinner
{
    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>|null
     */
    public function execute(array $state): ?array
    {
        $playerHand = $this->normalizeHand($state['bestHand'] ?? null);
        $opponentHand = $this->normalizeHand($state['opponentBestHand'] ?? null);

        if ($playerHand === null || $opponentHand === null) {
            return null;
        }

        $comparison = $this->compareHands($playerHand, $opponentHand);

        if ($comparison > 0) {
            return $this->winner('player', 'Você', $playerHand['name']);
        }

        if ($comparison < 0) {
            return $this->winner('opponent', 'Oponente', $opponentHand['name']);
        }

        return $this->winner('tie', 'Empate', $playerHand['name']);
    }

    /**
     * @param mixed $hand
     * @return array{name: string, rank: int, kickers: array<int, int>}|null
     */
    private function normalizeHand(mixed $hand): ?array
    {
        if (! is_array($hand)) {
            return null;
        }

        if (! isset($hand['name'], $hand['rank'])) {
            return null;
        }

        $kickers = $hand['kickers'] ?? [];

        if (! is_array($kickers)) {
            $kickers = [];
        }

        return [
            'name' => (string) $hand['name'],
            'rank' => (int) $hand['rank'],
            'kickers' => array_map('intval', array_values($kickers)),
        ];
    }

    /**
     * @param array{name: string, rank: int, kickers: array<int, int>} $playerHand
     * @param array{name: string, rank: int, kickers: array<int, int>} $opponentHand
     */
    private function compareHands(array $playerHand, array $opponentHand): int
    {
        if ($playerHand['rank'] !== $opponentHand['rank']) {
            return $playerHand['rank'] <=> $opponentHand['rank'];
        }

        $totalKickers = max(count($playerHand['kickers']), count($opponentHand['kickers']));

        for ($index = 0; $index < $totalKickers; $index++) {
            $playerKicker = $playerHand['kickers'][$index] ?? 0;
            $opponentKicker = $opponentHand['kickers'][$index] ?? 0;

            if ($playerKicker !== $opponentKicker) {
                return $playerKicker <=> $opponentKicker;
            }
        }

        return 0;
    }

    /**
     * @return array{player: string, label: string, handName: string}
     */
    private function winner(string $player, string $label, string $handName): array
    {
        return [
            'player' => $player,
            'label' => $label,
            'handName' => $handName,
        ];
    }
}
