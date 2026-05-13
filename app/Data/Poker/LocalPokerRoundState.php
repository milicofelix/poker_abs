<?php

namespace App\Data\Poker;

use App\Domain\Poker\Game\RoundStreet;

final readonly class LocalPokerRoundState
{
    /**
     * @param array<string, mixed>|null $lastAction
     */
    public function __construct(
        public RoundStreet $street,
        public int $pot,
        public int $playerStack,
        public int $currentBet,
        public int $playerStreetBet = 0,
        public int $opponentStreetBet = 0,
        public int $amountToCall = 0,
        public int $minimumRaise = 10,
        public ?array $lastAction = null,
        public bool $isFinished = false,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            street: RoundStreet::tryFrom((string) ($payload['street'] ?? RoundStreet::PreFlop->value)) ?? RoundStreet::PreFlop,
            pot: (int) ($payload['pot'] ?? 0),
            playerStack: (int) ($payload['playerStack'] ?? 1000),
            currentBet: (int) ($payload['currentBet'] ?? 20),
            playerStreetBet: (int) ($payload['playerStreetBet'] ?? 0),
            opponentStreetBet: (int) ($payload['opponentStreetBet'] ?? 0),
            amountToCall: max(0, (int) ($payload['currentBet'] ?? 20) - (int) ($payload['playerStreetBet'] ?? 0)),
            minimumRaise: max(10, (int) ($payload['minimumRaise'] ?? 10)),
            lastAction: isset($payload['lastAction']) && is_array($payload['lastAction'])
                ? $payload['lastAction']
                : null,
            isFinished: (bool) ($payload['isFinished'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street->value,
            'streetLabel' => $this->street->label(),
            'pot' => $this->pot,
            'playerStack' => $this->playerStack,
            'currentBet' => $this->currentBet,
            'playerStreetBet' => $this->playerStreetBet,
            'opponentStreetBet' => $this->opponentStreetBet,
            'amountToCall' => $this->amountToCall,
            'minimumRaise' => $this->minimumRaise,
            'lastAction' => $this->lastAction,
            'isFinished' => $this->isFinished,
        ];
    }
}
