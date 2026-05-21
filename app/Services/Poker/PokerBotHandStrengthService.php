<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\RoundStreet;

final class PokerBotHandStrengthService
{
    public function __construct(private readonly PokerBotProbabilityService $probabilityService = new PokerBotProbabilityService())
    {
    }

    /**
     * @param array<string, mixed> $state
     * @return array{
     *     score: int,
     *     label: string,
     *     rank: int,
     *     hasPairInHand: bool,
     *     range: string,
     *     rangeLabel: string,
     *     boardTexture: string,
     *     boardTextureLabel: string,
     *     hasFlushDraw: bool,
     *     hasStraightDraw: bool,
     *     drawBonus: int,
     *     pressureBonus: int,
     *     probability: array<string, mixed>,
     *     outs: int,
     *     potOdds: int,
     *     equity: int,
     *     evScore: int
     * }
     */
    public function evaluate(array $state, string $actor): array
    {
        $street = RoundStreet::tryFrom((string) ($state['street'] ?? RoundStreet::PreFlop->value)) ?? RoundStreet::PreFlop;
        $cards = $actor === 'opponent'
            ? $this->cards($state['opponentCards'] ?? [])
            : $this->cards($state['playerCards'] ?? []);

        $communityCards = $this->cards($state['communityCards'] ?? []);
        $allCards = array_merge($cards, $communityCards);

        $bestHand = $actor === 'opponent'
            ? ($state['opponentBestHand'] ?? [])
            : ($state['bestHand'] ?? []);

        $rank = is_array($bestHand) ? (int) ($bestHand['rank'] ?? 1) : 1;
        $range = $this->preFlopRange($cards);
        $hasFlushDraw = $street !== RoundStreet::PreFlop && $this->hasFlushDraw($allCards);
        $hasStraightDraw = $street !== RoundStreet::PreFlop && $this->hasStraightDraw($allCards);
        $drawBonus = ($hasFlushDraw ? 12 : 0) + ($hasStraightDraw ? 10 : 0);
        $boardTexture = $this->boardTexture($communityCards);

        if ($hasFlushDraw && $boardTexture === 'connected') {
            $boardTexture = 'dangerous';
        }

        $pressureBonus = $this->pressureBonus($street, $range, $boardTexture, $hasFlushDraw, $hasStraightDraw);

        $probability = $this->probabilityService->estimate(
            street: $street,
            holeCards: $cards,
            communityCards: $communityCards,
            pot: (int) ($state['pot'] ?? 0),
            amountToCall: (int) ($state['amountToCall'] ?? 0),
            rank: $rank,
            hasFlushDraw: $hasFlushDraw,
            hasStraightDraw: $hasStraightDraw,
        );

        $score = $street === RoundStreet::PreFlop
            ? $this->preFlopScore($cards, $range)
            : $this->postFlopScore($rank, $range, $drawBonus, $pressureBonus, $boardTexture, $probability);

        return [
            'score' => $score,
            'label' => $this->label($score, $hasFlushDraw, $hasStraightDraw),
            'rank' => $rank,
            'hasPairInHand' => $this->hasPair($cards),
            'range' => $range,
            'rangeLabel' => $this->rangeLabel($range),
            'boardTexture' => $boardTexture,
            'boardTextureLabel' => $this->boardTextureLabel($boardTexture),
            'hasFlushDraw' => $hasFlushDraw,
            'hasStraightDraw' => $hasStraightDraw,
            'drawBonus' => $drawBonus,
            'pressureBonus' => $pressureBonus,
            'probability' => $probability,
            'outs' => $probability['outs'],
            'potOdds' => $probability['potOdds'],
            'equity' => $probability['equity'],
            'evScore' => $probability['evScore'],
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
    private function preFlopScore(array $cards, string $range): int
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

        $score += match ($range) {
            'premium' => 20,
            'strong' => 12,
            'speculative' => 4,
            'trash' => -10,
            default => 0,
        };

        return min(100, max(10, $score));
    }

    /**
     * @param array<string, mixed> $probability
     */
    private function postFlopScore(int $rank, string $range, int $drawBonus, int $pressureBonus, string $boardTexture, array $probability): int
    {
        $score = min(100, max(10, $rank * 16));
        $score += $drawBonus + $pressureBonus;

        $evScore = (int) ($probability['evScore'] ?? 0);
        $equity = (int) ($probability['equity'] ?? 0);

        if ($evScore >= 12) {
            $score += 10;
        } elseif ($evScore >= 0) {
            $score += 5;
        } elseif ($evScore <= -12 && $rank <= 2) {
            $score -= 10;
        }

        if ($equity >= 55 && $rank <= 2) {
            $score += 6;
        }

        if ($rank <= 1) {
            $score += match ($range) {
                'premium' => 12,
                'strong' => 7,
                'speculative' => 2,
                'trash' => -8,
                default => 0,
            };
        }

        if ($rank === 2 && $boardTexture === 'dangerous') {
            $score -= 8;
        }

        return min(100, max(10, $score));
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function preFlopRange(array $cards): string
    {
        if (count($cards) < 2) {
            return 'trash';
        }

        $first = $this->rankValue((string) ($cards[0]['rank'] ?? '2'));
        $second = $this->rankValue((string) ($cards[1]['rank'] ?? '2'));
        $high = max($first, $second);
        $low = min($first, $second);
        $suited = ($cards[0]['suit'] ?? null) === ($cards[1]['suit'] ?? null);
        $gap = abs($first - $second);
        $pair = $this->hasPair($cards);

        if (($pair && $high >= 10) || ($high === 14 && $low >= 12) || ($high === 13 && $low === 12 && $suited)) {
            return 'premium';
        }

        if (($pair && $high >= 7) || ($high >= 13 && $low >= 10) || ($high === 14 && $low >= 9) || ($suited && $high >= 12 && $low >= 10)) {
            return 'strong';
        }

        if (($pair && $high >= 4) || ($suited && $gap <= 3 && $high >= 8) || ($gap <= 1 && $high >= 9)) {
            return 'speculative';
        }

        return 'trash';
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function hasPair(array $cards): bool
    {
        return count($cards) >= 2
            && (string) ($cards[0]['rank'] ?? '') === (string) ($cards[1]['rank'] ?? '');
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function hasFlushDraw(array $cards): bool
    {
        $suits = [];

        foreach ($cards as $card) {
            $suit = (string) ($card['suit'] ?? '');

            if ($suit !== '') {
                $suits[$suit] = ($suits[$suit] ?? 0) + 1;
            }
        }

        return $suits !== [] && max($suits) === 4;
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     */
    private function hasStraightDraw(array $cards): bool
    {
        $values = array_values(array_unique(array_map(
            fn (array $card): int => $this->rankValue((string) ($card['rank'] ?? '2')),
            $cards,
        )));

        if (in_array(14, $values, true)) {
            $values[] = 1;
        }

        sort($values);

        for ($start = 1; $start <= 10; $start++) {
            $needed = range($start, $start + 4);
            $matches = count(array_intersect($needed, $values));

            if ($matches >= 4) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $communityCards
     */
    private function boardTexture(array $communityCards): string
    {
        if (count($communityCards) < 3) {
            return 'unknown';
        }

        $suits = [];
        $values = [];

        foreach ($communityCards as $card) {
            $suit = (string) ($card['suit'] ?? '');
            $value = $this->rankValue((string) ($card['rank'] ?? '2'));

            if ($suit !== '') {
                $suits[$suit] = ($suits[$suit] ?? 0) + 1;
            }

            $values[] = $value;
        }

        $values = array_values(array_unique($values));
        sort($values);
        $maxSuitCount = $suits === [] ? 0 : max($suits);
        $maxGap = count($values) >= 2 ? max(array_map(static fn (array $pair): int => $pair[1] - $pair[0], array_map(null, array_slice($values, 0, -1), array_slice($values, 1)))) : 99;

        if ($maxSuitCount >= 3 || $maxGap <= 2) {
            return 'dangerous';
        }

        if ($maxSuitCount === 2 || $maxGap <= 4) {
            return 'connected';
        }

        return 'dry';
    }

    private function pressureBonus(RoundStreet $street, string $range, string $boardTexture, bool $hasFlushDraw, bool $hasStraightDraw): int
    {
        if ($street === RoundStreet::PreFlop) {
            return 0;
        }

        $bonus = match ($range) {
            'premium' => 8,
            'strong' => 5,
            'speculative' => 2,
            default => 0,
        };

        if ($boardTexture === 'dry') {
            $bonus += 6;
        }

        if ($boardTexture === 'dangerous' && ! $hasFlushDraw && ! $hasStraightDraw) {
            $bonus -= 6;
        }

        return $bonus;
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

    private function rangeLabel(string $range): string
    {
        return match ($range) {
            'premium' => 'range premium',
            'strong' => 'range forte',
            'speculative' => 'range especulativo',
            default => 'range fraco',
        };
    }

    private function boardTextureLabel(string $texture): string
    {
        return match ($texture) {
            'dangerous' => 'board perigoso',
            'connected' => 'board conectado',
            'dry' => 'board seco',
            default => 'board indefinido',
        };
    }

    private function label(int $score, bool $hasFlushDraw, bool $hasStraightDraw): string
    {
        if ($hasFlushDraw && $hasStraightDraw) {
            return 'combo draw';
        }

        if ($score >= 75) {
            return 'mão forte';
        }

        if ($hasFlushDraw) {
            return 'flush draw';
        }

        if ($hasStraightDraw) {
            return 'straight draw';
        }

        return $score >= 50 ? 'mão jogável' : 'mão fraca';
    }
}
