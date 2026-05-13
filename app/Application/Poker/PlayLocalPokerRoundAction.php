<?php

namespace App\Application\Poker;

use App\Services\Poker\LocalPokerEngineService;

final readonly class PlayLocalPokerRoundAction
{
    public function __construct(
        private LocalPokerEngineService $engine = new LocalPokerEngineService(),
    ) {
    }

    /**
     * @param array<string, mixed> $currentState
     * @return array<string, mixed>
     */
    public function execute(array $currentState, string $action, int $raiseAmount = 0): array
    {
        return $this->engine->play($currentState, $action, $raiseAmount);
    }
}
