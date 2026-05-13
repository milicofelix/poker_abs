<?php

namespace App\Data\Poker;

final readonly class HandConclusion
{
    /**
     * @param array<string, mixed>|null $winner
     */
    public function __construct(
        public bool $isFinished,
        public string $reason,
        public string $message,
        public ?array $winner = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'isFinished' => $this->isFinished,
            'reason' => $this->reason,
            'message' => $this->message,
            'winner' => $this->winner,
        ];
    }
}
