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
            return $this->winner('player', 'Você', $playerHand['name'], $playerHand['cards'], $playerHand['highlightCards']);
        }

        if ($comparison < 0) {
            return $this->winner('opponent', 'Oponente', $opponentHand['name'], $opponentHand['cards'], $opponentHand['highlightCards']);
        }

        return $this->winner('tie', 'Empate', $playerHand['name'], $playerHand['cards'], $playerHand['highlightCards']);
    }

    /**
     * @param mixed $hand
     * @return array{name: string, rank: int, kickers: array<int, int>, cards: array<int, mixed>, highlightCards: array<int, mixed>}|null
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
            'cards' => is_array($hand['cards'] ?? null) ? array_values($hand['cards']) : [],
            'highlightCards' => is_array($hand['highlightCards'] ?? $hand['highlight_cards'] ?? null)
                ? array_values($hand['highlightCards'] ?? $hand['highlight_cards'])
                : [],
        ];
    }

    /**
     * @param array{name: string, rank: int, kickers: array<int, int>, cards: array<int, mixed>, highlightCards: array<int, mixed>} $playerHand
     * @param array{name: string, rank: int, kickers: array<int, int>, cards: array<int, mixed>, highlightCards: array<int, mixed>} $opponentHand
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
     * @param array<int, mixed> $cards
     * @return array{player: string, label: string, handName: string, cards: array<int, mixed>, highlightCards: array<int, mixed>}
     */
    private function winner(string $player, string $label, string $handName, array $cards, array $highlightCards = []): array
    {
        return [
            'player' => $player,
            'label' => $label,
            'handName' => $handName,
            'cards' => $cards,
            'highlightCards' => $highlightCards !== [] ? $highlightCards : $cards,
        ];
    }
}
