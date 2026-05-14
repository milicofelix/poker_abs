<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\RoundStreet;

final class PokerBotHandStrengthService
{
    /**
     * @param array<string, mixed> $state
     * @return array{score: int, label: string, rank: int, hasPairInHand: bool}
     */
    public function evaluate(array $state, string $actor): array
    {
        $street = RoundStreet::tryFrom((string) ($state['street'] ?? RoundStreet::PreFlop->value)) ?? RoundStreet::PreFlop;
        $cards = $actor === 'opponent'
            ? $this->cards($state['opponentCards'] ?? [])
            : $this->cards($state['playerCards'] ?? []);

        $bestHand = $actor === 'opponent'
            ? ($state['opponentBestHand'] ?? [])
            : ($state['bestHand'] ?? []);

        $rank = is_array($bestHand) ? (int) ($bestHand['rank'] ?? 1) : 1;
        $score = $street === RoundStreet::PreFlop
            ? $this->preFlopScore($cards)
            : min(100, max(10, $rank * 16));

        return [
            'score' => $score,
            'label' => $this->label($score),
            'rank' => $rank,
            'hasPairInHand' => $this->hasPair($cards),
        ];
    }

    /**
     * @param mixed $cards
     * @return array<int, array<string, mixed>>
     */
    private function cards(mixed $cards): array
    {
        if (! is_array($cards)) {
            return [];
        }

        return array_values(array_filter($cards, static fn (mixed $card): bool => is_array($card)));
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function preFlopScore(array $cards): int
    {
        if (count($cards) < 2) {
            return 25;
        }

        $first = $this->rankValue((string) ($cards[0]['rank'] ?? '2'));
        $second = $this->rankValue((string) ($cards[1]['rank'] ?? '2'));
        $high = max($first, $second);
        $low = min($first, $second);
        $score = $high + $low;

        if ($this->hasPair($cards)) {
            $score += 28 + $high;
        }

        if (($cards[0]['suit'] ?? null) === ($cards[1]['suit'] ?? null)) {
            $score += 8;
        }

        if (abs($first - $second) <= 1) {
            $score += 6;
        }

        if ($high >= 14 && $low >= 10) {
            $score += 16;
        }

        return min(100, max(10, $score));
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function hasPair(array $cards): bool
    {
        return count($cards) >= 2
            && (string) ($cards[0]['rank'] ?? '') === (string) ($cards[1]['rank'] ?? '');
    }

    private function rankValue(string $rank): int
    {
        return match ($rank) {
            'A' => 14,
            'K' => 13,
            'Q' => 12,
            'J' => 11,
            'T', '10' => 10,
            default => max(2, min(9, (int) $rank)),
        };
    }

    private function label(int $score): string
    {
        return match (true) {
            $score >= 75 => 'mão forte',
            $score >= 50 => 'mão jogável',
            default => 'mão fraca',
        };
    }
}
