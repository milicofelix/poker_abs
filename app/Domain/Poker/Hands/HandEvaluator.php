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

        $cards = array_values($cards);
        $rankValues = $this->rankValues($cards);
        $rankCounts = array_count_values($rankValues);
        arsort($rankCounts);

        $flushValues = $this->flushValues($cards);
        $straightHigh = $this->straightHighCard($rankValues);
        $straightFlushHigh = $this->straightFlushHighCard($cards);

        if ($straightFlushHigh !== null) {
            return new PokerHand(HandRank::StraightFlush, [$straightFlushHigh]);
        }

        $four = $this->valuesWithCount($rankCounts, 4);
        if ($four !== []) {
            $quad = max($four);
            return new PokerHand(HandRank::FourOfAKind, [
                $quad,
                $this->highestExcept($rankValues, [$quad]),
            ]);
        }

        $three = $this->valuesWithCount($rankCounts, 3);
        $pairs = $this->valuesWithCount($rankCounts, 2);

        if ($three !== [] && ($pairs !== [] || count($three) > 1)) {
            rsort($three);
            rsort($pairs);

            return new PokerHand(HandRank::FullHouse, [
                $three[0],
                $pairs[0] ?? $three[1],
            ]);
        }

        if ($flushValues !== []) {
            rsort($flushValues);
            return new PokerHand(HandRank::Flush, array_slice($flushValues, 0, 5));
        }

        if ($straightHigh !== null) {
            return new PokerHand(HandRank::Straight, [$straightHigh]);
        }

        if ($three !== []) {
            $triple = max($three);
            return new PokerHand(HandRank::ThreeOfAKind, array_merge(
                [$triple],
                $this->highestExceptMany($rankValues, [$triple], 2)
            ));
        }

        if (count($pairs) >= 2) {
            rsort($pairs);
            $topPairs = array_slice($pairs, 0, 2);

            return new PokerHand(HandRank::TwoPair, array_merge(
                $topPairs,
                [$this->highestExcept($rankValues, $topPairs)]
            ));
        }

        if (count($pairs) === 1) {
            $pair = $pairs[0];

            return new PokerHand(HandRank::Pair, array_merge(
                [$pair],
                $this->highestExceptMany($rankValues, [$pair], 3)
            ));
        }

        rsort($rankValues);

        return new PokerHand(HandRank::HighCard, array_slice(array_values(array_unique($rankValues)), 0, 5));
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
