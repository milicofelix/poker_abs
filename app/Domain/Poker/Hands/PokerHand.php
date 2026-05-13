<?php

namespace App\Domain\Poker\Hands;

use App\Domain\Poker\Cards\Card;

final readonly class PokerHand
{
    /**
     * @param array<int, int> $kickers
     * @param array<int, Card> $cards
     */
    public function __construct(
        public HandRank $rank,
        public array $kickers = [],
        private array $cards = [],
    ) {
    }

    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return $this->cards;
    }

    public function label(): string
    {
        return $this->rank->label();
    }
}