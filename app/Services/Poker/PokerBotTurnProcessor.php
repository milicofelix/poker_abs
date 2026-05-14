<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\RoundStreet;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;

final readonly class PokerBotTurnProcessor
{
    public function __construct(
        private PokerBotDecisionService $decisionService,
        private PokerBotHandStrengthService $handStrengthService,
        private PokerTableTurnActionService $turnAction,
    ) {
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function process(PokerTable $table, array $state): array
    {
        for ($attempt = 0; $attempt < 4; $attempt++) {
            if ((bool) ($state['isFinished'] ?? false)) {
                break;
            }

            $actor = $this->currentActor($state);
            $bot = $this->botForActor($table, $actor);

            if (! $bot) {
                break;
            }

            $street = RoundStreet::tryFrom((string) ($state['street'] ?? RoundStreet::PreFlop->value)) ?? RoundStreet::PreFlop;
            $amountToCall = (int) ($state['amountToCall'] ?? 0);
            $currentBet = (int) ($state['currentBet'] ?? 0);
            $botStack = $actor === 'opponent'
                ? (int) ($state['opponentStack'] ?? 0)
                : (int) ($state['playerStack'] ?? 0);
            $strength = $this->handStrengthService->evaluate($state, $actor);

            $decision = $this->decisionService->decide(
                (string) ($bot->bot_profile ?: 'conservative'),
                (string) ($bot->bot_difficulty ?: 'normal'),
                $street,
                $amountToCall,
                $currentBet,
                $botStack,
                $strength,
            );

            $state = $this->turnAction->executeBot(
                $state,
                $actor,
                $decision->action->value,
                $decision->amount,
            );

            $state['botDecision'] = [
                'processed' => true,
                'actor' => $actor,
                'profile' => $bot->bot_profile,
                'difficulty' => $bot->bot_difficulty,
                'action' => $decision->action->value,
                'amount' => $decision->amount,
                'handStrength' => $strength,
                'message' => $decision->message,
            ];

            if (isset($state['lastAction']) && is_array($state['lastAction'])) {
                $state['lastAction']['message'] = $decision->message;
                $state['lastAction']['isBot'] = true;
                $state['lastAction']['botProfile'] = $bot->bot_profile;
                $state['lastAction']['handStrength'] = $strength;
            }
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function currentActor(array $state): string
    {
        $actor = (string) data_get($state, 'currentTurn.actor', 'player');

        return $actor === 'opponent' ? 'opponent' : 'player';
    }

    private function botForActor(PokerTable $table, string $actor): ?PokerTablePlayer
    {
        $seatNumber = $actor === 'opponent' ? 2 : 1;

        /** @var PokerTablePlayer|null $bot */
        $bot = $table->realPlayers()
            ->where('seat_number', $seatNumber)
            ->where('is_bot', true)
            ->whereNull('left_at')
            ->first();

        return $bot;
    }
}
