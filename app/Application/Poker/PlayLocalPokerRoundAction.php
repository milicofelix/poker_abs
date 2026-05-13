<?php

namespace App\Application\Poker;

use App\Data\Poker\LocalPokerRoundState;
use App\Data\Poker\PokerRoundResult;
use App\Domain\Poker\Game\OpponentDecision;
use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Domain\Poker\Game\SimpleOpponentStrategy;
use App\Services\Poker\ResolveLocalHandConclusion;
use App\Services\Poker\ResolveLocalShowdownWinner;
use InvalidArgumentException;

final class PlayLocalPokerRoundAction
{
    public function __construct(
        private readonly SimpleOpponentStrategy $opponentStrategy = new SimpleOpponentStrategy(),
        private readonly ResolveLocalHandConclusion $resolveConclusion = new ResolveLocalHandConclusion(),
        private readonly ResolveLocalShowdownWinner $resolveShowdownWinner = new ResolveLocalShowdownWinner(),
    ) {
    }

    /**
     * @param array<string, mixed> $currentState
     * @return array<string, mixed>
     */
    public function execute(array $currentState, string $action, int $raiseAmount = 0): array
    {
        $pokerAction = PokerAction::tryFrom($action);

        if (! $pokerAction) {
            throw new InvalidArgumentException('Ação inválida para a rodada.');
        }

        $roundState = LocalPokerRoundState::fromArray($currentState);

        $street = $roundState->street;
        $pot = $roundState->pot;
        $playerStack = $roundState->playerStack;
        $opponentStack = (int) ($currentState['opponentStack'] ?? 1000);
        $currentBet = $roundState->currentBet;
        $playerStreetBet = $roundState->playerStreetBet;
        $opponentStreetBet = $roundState->opponentStreetBet;
        $minimumRaise = $roundState->minimumRaise;
        $actionHistory = $this->normalizeActionHistory($currentState['actionHistory'] ?? []);

        $amountToCall = max(0, $currentBet - $playerStreetBet);
        $playerAmount = 0;

        $message = match ($pokerAction) {
            PokerAction::Check => $amountToCall > 0
                ? 'Você não podia pedir mesa e pagou a aposta pendente.'
                : 'Você pediu mesa.',
            PokerAction::Call => 'Você pagou a aposta.',
            PokerAction::Raise => 'Você aumentou a aposta.',
            PokerAction::Fold => 'Você desistiu da mão.',
        };

        if ($pokerAction === PokerAction::Check && $amountToCall > 0) {
            $pokerAction = PokerAction::Call;
        }

        if ($pokerAction === PokerAction::Call) {
            $playerAmount = min($amountToCall, $playerStack);
            $playerStreetBet += $playerAmount;
            $playerStack -= $playerAmount;
            $pot += $playerAmount;
        }

        if ($pokerAction === PokerAction::Raise) {
            $previousBet = $currentBet;
            $targetBet = max($currentBet + $minimumRaise, $raiseAmount);
            $playerAmount = min(max(0, $targetBet - $playerStreetBet), $playerStack);

            $playerStreetBet += $playerAmount;
            $playerStack -= $playerAmount;
            $pot += $playerAmount;
            $currentBet = max($currentBet, $playerStreetBet);
            $minimumRaise = max(10, $currentBet - $previousBet);
        }

        $actionHistory[] = [
            'street' => $street->label(),
            'action' => $pokerAction->label(),
            'amount' => $playerAmount,
            'message' => $message,
            'pot' => $pot,
            'playerStack' => $playerStack,
            'playerStreetBet' => $playerStreetBet,
            'actor' => 'player',
        ];

        $opponentAction = null;

        if ($pokerAction !== PokerAction::Fold) {
            $opponentDecision = $this->opponentStrategy->decide($street, $currentBet, $opponentStack);
            $opponentAction = $opponentDecision->action;

            if ($opponentDecision->action === PokerAction::Call) {
                $opponentAmount = min(max(0, $currentBet - $opponentStreetBet), $opponentStack);
                $opponentStreetBet += $opponentAmount;
                $opponentStack -= $opponentAmount;
                $pot += $opponentAmount;
                $opponentDecision = new OpponentDecision(
                    action: $opponentDecision->action,
                    amount: $opponentAmount,
                    message: $opponentDecision->message,
                );
            }

            $opponentHistory = $opponentDecision->toArray($street, $pot, $opponentStack);
            $opponentHistory['opponentStreetBet'] = $opponentStreetBet;
            $actionHistory[] = $opponentHistory;
        }

        $nextStreet = $street;

        if ($pokerAction !== PokerAction::Fold && $opponentAction !== PokerAction::Fold) {
            $nextStreet = $street->next();
        }

        $handAdvanced = $nextStreet !== $street;

        if ($handAdvanced) {
            $currentBet = 0;
            $playerStreetBet = 0;
            $opponentStreetBet = 0;
            $minimumRaise = 10;
        }

        $winner = $nextStreet === RoundStreet::Showdown
            ? $this->resolveShowdownWinner->execute($currentState)
            : null;

        $conclusion = $this->resolveConclusion->execute($pokerAction, $opponentAction, $nextStreet, $winner);
        $nextAmountToCall = max(0, $currentBet - $playerStreetBet);

        $nextRoundState = new LocalPokerRoundState(
            street: $nextStreet,
            pot: $pot,
            playerStack: $playerStack,
            currentBet: $currentBet,
            playerStreetBet: $playerStreetBet,
            opponentStreetBet: $opponentStreetBet,
            amountToCall: $nextAmountToCall,
            minimumRaise: $minimumRaise,
            lastAction: [
                'type' => $pokerAction->value,
                'label' => $pokerAction->label(),
                'message' => $message,
                'amount' => $playerAmount,
            ],
            isFinished: $conclusion->isFinished,
        );

        return [
            ...(new PokerRoundResult($currentState, $nextRoundState))->toArray(),
            'opponentStack' => $opponentStack,
            'actionHistory' => $actionHistory,
            'conclusion' => $conclusion->toArray(),
        ];
    }

    /**
     * @param mixed $history
     * @return array<int, array<string, mixed>>
     */
    private function normalizeActionHistory(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter(
            $history,
            static fn (mixed $item): bool => is_array($item),
        ));
    }
}
