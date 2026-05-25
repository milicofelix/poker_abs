<?php

namespace App\Domain\Poker\Hands;

use App\Domain\Poker\Cards\Card;

final readonly class PokerHand
{
    /**
     * @param array<int, int> $kickers
     * @param array<int, Card> $cards
     * @param array<int, Card> $highlightCards
     */
    public function __construct(
        public HandRank $rank,
        public array $kickers = [],
        private array $cards = [],
        private array $highlightCards = [],
    ) {
    }

    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return $this->cards;
    }

    /**
     * Retorna apenas as cartas que formam o jogo principal para destaque visual.
     * Ex.: em trinca, retorna só as 3 cartas da trinca; os kickers ficam fora.
     *
     * @return array<int, Card>
     */
    public function highlightCards(): array
    {
        return $this->highlightCards !== [] ? $this->highlightCards : $this->cards;
    }

    public function label(): string
    {
        return $this->rank->label();
    }
}