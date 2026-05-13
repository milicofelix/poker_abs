<?php

namespace App\Domain\Poker\Cards;

use RuntimeException;

final class Deck
{
    /**
     * @param array<int, Card> $cards
     */
    private function __construct(
        private array $cards,
    ) {
    }

    public static function standard(): self
    {
        $cards = [];

        foreach (Suit::cases() as $suit) {
            foreach (Rank::cases() as $rank) {
                $cards[] = new Card($suit, $rank);
            }
        }

        return new self($cards);
    }

    public function shuffle(): self
    {
        shuffle($this->cards);

        return $this;
    }

    /**
     * @return array<int, Card>
     */
    public function draw(int $quantity = 1): array
    {
        if ($quantity < 1) {
            throw new RuntimeException('A quantidade de cartas deve ser maior que zero.');
        }

        if ($quantity > $this->remaining()) {
            throw new RuntimeException('Não há cartas suficientes no baralho.');
        }

        return array_splice($this->cards, 0, $quantity);
    }

    public function remaining(): int
    {
        return count($this->cards);
    }

    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return $this->cards;
    }
}