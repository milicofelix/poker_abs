<?php

namespace App\Domain\Poker\Hands;

use App\Domain\Poker\Cards\Card;
use InvalidArgumentException;

final class HandEvaluator
{
    /**
     * @param array<int, Card> $cards
     */
    public function evaluate(array $cards): PokerHand
    {
        if (count($cards) < 5) {
            throw new InvalidArgumentException('É necessário informar pelo menos 5 cartas para avaliar uma mão.');
        }

        $bestHand = null;

        foreach ($this->fiveCardCombinations(array_values($cards)) as $combination) {
            $candidate = $this->evaluateFiveCards($combination);

            if ($bestHand === null || $this->compareHands($candidate, $bestHand) > 0) {
                $bestHand = $candidate;
            }
        }

        return $bestHand;
    }

    /**
     * @param array<int, Card> $cards
     */
    private function evaluateFiveCards(array $cards): PokerHand
    {
        $rankValues = $this->rankValues($cards);
        $rankCounts = array_count_values($rankValues);
        arsort($rankCounts);

        $flushValues = $this->flushValues($cards);
        $straightHigh = $this->straightHighCard($rankValues);
        $straightFlushHigh = $this->straightFlushHighCard($cards);

        if ($straightFlushHigh !== null) {
            return new PokerHand(HandRank::StraightFlush, [$straightFlushHigh], $this->sortCardsForShowdown($cards), $this->sortCardsForShowdown($cards));
        }

        $four = $this->valuesWithCount($rankCounts, 4);
        if ($four !== []) {
            $quad = max($four);
            return new PokerHand(HandRank::FourOfAKind, [
                $quad,
                $this->highestExcept($rankValues, [$quad]),
            ], $this->sortCardsForShowdown($cards), $this->cardsMatchingValues($cards, [$quad]));
        }

        $three = $this->valuesWithCount($rankCounts, 3);
        $pairs = $this->valuesWithCount($rankCounts, 2);

        if ($three !== [] && ($pairs !== [] || count($three) > 1)) {
            rsort($three);
            rsort($pairs);

            return new PokerHand(HandRank::FullHouse, [
                $three[0],
                $pairs[0] ?? $three[1],
            ], $this->sortCardsForShowdown($cards), $this->sortCardsForShowdown($cards));
        }

        if ($flushValues !== []) {
            rsort($flushValues);
            return new PokerHand(HandRank::Flush, array_slice($flushValues, 0, 5), $this->sortCardsForShowdown($cards), $this->sortCardsForShowdown($cards));
        }

        if ($straightHigh !== null) {
            return new PokerHand(HandRank::Straight, [$straightHigh], $this->sortCardsForShowdown($cards), $this->sortCardsForShowdown($cards));
        }

        if ($three !== []) {
            $triple = max($three);
            return new PokerHand(HandRank::ThreeOfAKind, array_merge(
                [$triple],
                $this->highestExceptMany($rankValues, [$triple], 2)
            ), $this->sortCardsForShowdown($cards), $this->cardsMatchingValues($cards, [$triple]));
        }

        if (count($pairs) >= 2) {
            rsort($pairs);
            $topPairs = array_slice($pairs, 0, 2);

            return new PokerHand(HandRank::TwoPair, array_merge(
                $topPairs,
                [$this->highestExcept($rankValues, $topPairs)]
            ), $this->sortCardsForShowdown($cards), $this->cardsMatchingValues($cards, $topPairs));
        }

        if (count($pairs) === 1) {
            $pair = $pairs[0];

            return new PokerHand(HandRank::Pair, array_merge(
                [$pair],
                $this->highestExceptMany($rankValues, [$pair], 3)
            ), $this->sortCardsForShowdown($cards), $this->cardsMatchingValues($cards, [$pair]));
        }

        rsort($rankValues);

        return new PokerHand(HandRank::HighCard, array_slice(array_values(array_unique($rankValues)), 0, 5), $this->sortCardsForShowdown($cards), $this->sortCardsForShowdown($cards));
    }

    /**
     * @param array<int, Card> $cards
     * @return array<int, array<int, Card>>
     */
    private function fiveCardCombinations(array $cards): array
    {
        $combinations = [];
        $total = count($cards);

        for ($a = 0; $a < $total - 4; $a++) {
            for ($b = $a + 1; $b < $total - 3; $b++) {
                for ($c = $b + 1; $c < $total - 2; $c++) {
                    for ($d = $c + 1; $d < $total - 1; $d++) {
                        for ($e = $d + 1; $e < $total; $e++) {
                            $combinations[] = [$cards[$a], $cards[$b], $cards[$c], $cards[$d], $cards[$e]];
                        }
                    }
                }
            }
        }

        return $combinations;
    }

    private function compareHands(PokerHand $left, PokerHand $right): int
    {
        $rankComparison = $left->rank->value <=> $right->rank->value;

        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        $max = max(count($left->kickers), count($right->kickers));

        for ($index = 0; $index < $max; $index++) {
            $comparison = ($left->kickers[$index] ?? 0) <=> ($right->kickers[$index] ?? 0);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    /**
     * @param array<int, Card> $cards
     * @param array<int, int> $values
     * @return array<int, Card>
     */
    private function cardsMatchingValues(array $cards, array $values): array
    {
        $wanted = array_map('intval', $values);

        return $this->sortCardsForShowdown(array_values(array_filter(
            $cards,
            static fn (Card $card): bool => in_array($card->rank->value(), $wanted, true)
        )));
    }

    /**
     * @param array<int, Card> $cards
     * @return array<int, Card>
     */
    private function sortCardsForShowdown(array $cards): array
    {
        usort(
            $cards,
            static fn (Card $left, Card $right): int => $right->rank->value() <=> $left->rank->value()
        );

        return array_values($cards);
    }

    /**
     * @param array<int, Card> $cards
     * @return array<int, int>
     */
    private function rankValues(array $cards): array
    {
        return array_map(
            static fn (Card $card): int => $card->rank->value(),
            $cards
        );
    }

    /**
     * @param array<int, Card> $cards
     * @return array<int, int>
     */
    private function flushValues(array $cards): array
    {
        $bySuit = [];

        foreach ($cards as $card) {
            $bySuit[$card->suit->value][] = $card->rank->value();
        }

        foreach ($bySuit as $values) {
            if (count($values) >= 5) {
                return $values;
            }
        }

        return [];
    }

    /**
     * @param array<int, Card> $cards
     */
    private function straightFlushHighCard(array $cards): ?int
    {
        $bySuit = [];

        foreach ($cards as $card) {
            $bySuit[$card->suit->value][] = $card->rank->value();
        }

        foreach ($bySuit as $values) {
            if (count($values) >= 5) {
                $straightHigh = $this->straightHighCard($values);

                if ($straightHigh !== null) {
                    return $straightHigh;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, int> $values
     */
    private function straightHighCard(array $values): ?int
    {
        $values = array_values(array_unique($values));
        rsort($values);

        if (in_array(14, $values, true)) {
            $values[] = 1;
        }

        $sequence = 1;
        $previous = null;

        foreach ($values as $value) {
            if ($previous === null) {
                $previous = $value;
                continue;
            }

            if ($previous - 1 === $value) {
                $sequence++;

                if ($sequence >= 5) {
                    return $value + 4;
                }
            } else {
                $sequence = 1;
            }

            $previous = $value;
        }

        return null;
    }

    /**
     * @param array<int, int> $rankCounts
     * @return array<int, int>
     */
    private function valuesWithCount(array $rankCounts, int $count): array
    {
        $values = [];

        foreach ($rankCounts as $value => $amount) {
            if ($amount === $count) {
                $values[] = (int) $value;
            }
        }

        rsort($values);

        return $values;
    }

    /**
     * @param array<int, int> $values
     * @param array<int, int> $except
     */
    private function highestExcept(array $values, array $except): int
    {
        return $this->highestExceptMany($values, $except, 1)[0];
    }

    /**
     * @param array<int, int> $values
     * @param array<int, int> $except
     * @return array<int, int>
     */
    private function highestExceptMany(array $values, array $except, int $quantity): array
    {
        $values = array_values(array_unique(array_filter(
            $values,
            static fn (int $value): bool => ! in_array($value, $except, true)
        )));

        rsort($values);

        return array_slice($values, 0, $quantity);
    }
}
