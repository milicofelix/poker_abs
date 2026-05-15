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
        private PokerBotStatTrackingService $statTracking,
        private PokerBotMemoryAdaptationService $memoryAdaptation,
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

            $this->waitBeforeBotAction();

            $street = RoundStreet::tryFrom((string) ($state['street'] ?? RoundStreet::PreFlop->value)) ?? RoundStreet::PreFlop;
            $amountToCall = (int) ($state['amountToCall'] ?? 0);
            $currentBet = (int) ($state['currentBet'] ?? 0);
            $botStack = $actor === 'opponent'
                ? (int) ($state['opponentStack'] ?? 0)
                : (int) ($state['playerStack'] ?? 0);
            $strength = $this->handStrengthService->evaluate($state, $actor);
            $context = $this->tableContext($state, $actor, $botStack);
            $memory = $this->memoryAdaptation->analyze($bot, $state, $actor);
            $context['memory'] = $memory;
            $strength = array_merge($strength, $context, [
                'context' => $context,
                'memory' => $memory,
                'memoryAdjustment' => (int) $memory['scoreAdjustment'],
                'bluffPressure' => (int) $memory['bluffPressure'],
                'callDownBias' => (int) $memory['callDownBias'],
            ]);

            $decision = $this->decisionService->decide(
                (string) ($bot->bot_profile ?: 'conservative'),
                (string) ($bot->bot_difficulty ?: 'normal'),
                $street,
                $amountToCall,
                $currentBet,
                $botStack,
                $strength,
            );

            $decisionContext = $state;

            $state = $this->turnAction->executeBot(
                $state,
                $actor,
                $decision->action->value,
                $decision->amount,
            );

            $this->statTracking->recordDecision(
                $table,
                $bot,
                $actor,
                $decisionContext,
                $strength,
                $decision,
            );

            $stats = $this->statTracking->summarizeForBot($bot);

            $state['botDecision'] = [
                'processed' => true,
                'actor' => $actor,
                'thinkingDelaySeconds' => $this->thinkingDelaySeconds(),
                'profile' => $bot->bot_profile,
                'difficulty' => $bot->bot_difficulty,
                'action' => $decision->action->value,
                'amount' => $decision->amount,
                'handStrength' => $strength,
                'message' => $decision->message,
                'stats' => $stats,
                'context' => $context,
                'memory' => $memory,
            ];

            if (isset($state['lastAction']) && is_array($state['lastAction'])) {
                $state['lastAction']['message'] = $decision->message;
                $state['lastAction']['isBot'] = true;
                $state['lastAction']['botThinkingDelaySeconds'] = $this->thinkingDelaySeconds();
                $state['lastAction']['botProfile'] = $bot->bot_profile;
                $state['lastAction']['handStrength'] = $strength;
                $state['lastAction']['botStats'] = $stats;
                $state['lastAction']['botContext'] = $context;
                $state['lastAction']['botMemory'] = $memory;
            }
        }

        return $state;
    }


    /**
     * @param array<string, mixed> $state
     * @return array{pot:int,spr:float,potPressure:string,stackPressure:string,opponentAggressionRate:int,opponentFoldRate:int,recentOpponentActions:int}
     */
    private function tableContext(array $state, string $actor, int $botStack): array
    {
        $pot = max(0, (int) ($state['pot'] ?? 0));
        $spr = $pot > 0 ? round($botStack / max(1, $pot), 2) : (float) $botStack;
        $history = is_array($state['actionHistory'] ?? null) ? $state['actionHistory'] : [];
        $opponentActor = $actor === 'opponent' ? 'player' : 'opponent';
        $recentOpponentActions = array_values(array_filter(
            array_slice($history, -8),
            static fn (mixed $action): bool => is_array($action) && (string) ($action['actor'] ?? '') === $opponentActor,
        ));

        $total = count($recentOpponentActions);
        $raises = count(array_filter($recentOpponentActions, static fn (array $action): bool => (string) ($action['action'] ?? '') === 'raise'));
        $folds = count(array_filter($recentOpponentActions, static fn (array $action): bool => (string) ($action['action'] ?? '') === 'fold'));

        return [
            'pot' => $pot,
            'spr' => $spr,
            'potPressure' => $pot >= 240 ? 'large_pot' : ($pot <= 60 ? 'small_pot' : 'normal_pot'),
            'stackPressure' => $botStack <= max(120, $pot) ? 'short_stack' : ($botStack >= max(900, $pot * 5) ? 'deep_stack' : 'comfortable'),
            'opponentAggressionRate' => $total > 0 ? (int) round(($raises / $total) * 100) : 0,
            'opponentFoldRate' => $total > 0 ? (int) round(($folds / $total) * 100) : 0,
            'recentOpponentActions' => $total,
        ];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function waitBeforeBotAction(): void
    {
        $delay = $this->thinkingDelaySeconds();

        if ($delay <= 0) {
            return;
        }

        sleep($delay);
    }

    private function thinkingDelaySeconds(): int
    {
        if (app()->environment('testing')) {
            return 0;
        }

        return max(0, (int) config('poker.bot_thinking_seconds', env('POKER_BOT_THINKING_SECONDS', 5)));
    }

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
