<?php

namespace App\Domain\Poker\Game;

final readonly class OpponentDecision
{
    public function __construct(
        public PokerAction $action,
        public int $amount,
        public string $message,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(RoundStreet $street, int $pot, int $opponentStack): array
    {
        return [
            'street' => $street->label(),
            'action' => $this->action->label(),
            'amount' => $this->amount,
            'message' => $this->message,
            'pot' => $pot,
            'opponentStack' => $opponentStack,
            'actor' => 'opponent',
        ];
    }
}
