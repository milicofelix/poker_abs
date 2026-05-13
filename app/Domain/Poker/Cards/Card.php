<?php

namespace App\Domain\Poker\Cards;

final readonly class Card
{
    public function __construct(
        public Suit $suit,
        public Rank $rank,
    ) {
    }

    public function label(): string
    {
        return "{$this->rank->value}{$this->symbol()}";
    }

    private function symbol(): string
    {
        return match ($this->suit) {
            Suit::Clubs => '♣',
            Suit::Diamonds => '♦',
            Suit::Hearts => '♥',
            Suit::Spades => '♠',
        };
    }
}