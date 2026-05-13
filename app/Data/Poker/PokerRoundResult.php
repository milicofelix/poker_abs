<?php

namespace App\Data\Poker;

final readonly class PokerRoundResult
{
    /**
     * @param array<string, mixed> $baseState
     */
    public function __construct(
        private array $baseState,
        private LocalPokerRoundState $roundState,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->baseState,
            ...$this->roundState->toArray(),
        ];
    }
}
