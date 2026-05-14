<?php

namespace App\Services\Poker;

use App\Domain\Poker\Game\OpponentDecision;
use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Support\Poker\PokerBotProfiles;

final class PokerBotDecisionService
{
    /**
     * @param array{score?: int, label?: string, rank?: int, hasPairInHand?: bool} $handStrength
     */
    public function decide(
        string $profile,
        string $difficulty,
        RoundStreet $street,
        int $amountToCall,
        int $currentBet,
        int $botStack,
        array $handStrength = [],
    ): OpponentDecision {
        if ($botStack <= 0) {
            return new OpponentDecision(
                action: PokerAction::Check,
                amount: 0,
                message: 'Bot sem fichas disponíveis.',
            );
        }

        $settings = PokerBotProfiles::difficulty($difficulty);
        $score = max(0, min(100, (int) ($handStrength['score'] ?? 35) + (int) $settings['score_bonus']));
        $label = (string) ($handStrength['label'] ?? 'força indefinida');

        return match ($profile) {
            'aggressive' => $this->aggressive($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
            default => $this->conservative($difficulty, $street, $amountToCall, $currentBet, $botStack, $score, $label, $settings),
        };
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function conservative(
        string $difficulty,
        RoundStreet $street,
        int $amountToCall,
        int $currentBet,
        int $botStack,
        int $score,
        string $label,
        array $settings,
    ): OpponentDecision {
        $callLimit = match ($difficulty) {
            'hard' => 110,
            'normal' => 70,
            default => 40,
        } + (int) $settings['call_bias'];

        $raiseThreshold = 82 + (int) $settings['raise_bias'];

        if ($score >= $raiseThreshold && $botStack > $amountToCall + 40) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $difficulty === 'hard' ? 60 : 40),
                $this->message('Bot conservador aumentou', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot conservador pediu mesa', $difficulty, $label));
        }

        $adjustedLimit = max(10, $callLimit + (int) floor($score / 2));
        $stackPressureLimit = (int) floor($botStack * ($difficulty === 'hard' ? 0.18 : 0.12));
        $foldThreshold = 45 + (int) $settings['fold_bias'];

        if ($score < $foldThreshold && $amountToCall > min($adjustedLimit, $stackPressureLimit)) {
            return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot conservador desistiu', $difficulty, $label));
        }

        return new OpponentDecision(
            PokerAction::Call,
            min($amountToCall, $botStack),
            $this->message('Bot conservador pagou', $difficulty, $label),
        );
    }

    /**
     * @param array<string, int|string> $settings
     */
    private function aggressive(
        string $difficulty,
        RoundStreet $street,
        int $amountToCall,
        int $currentBet,
        int $botStack,
        int $score,
        string $label,
        array $settings,
    ): OpponentDecision {
        $pressureLimit = match ($difficulty) {
            'hard' => 190,
            'normal' => 130,
            default => 80,
        } + (int) $settings['call_bias'];

        $raiseThreshold = 65 + (int) $settings['raise_bias'];
        $bluffThreshold = (int) $settings['bluff_score'];
        $canPressure = $botStack > $amountToCall + 60;

        if ($canPressure && ($score >= $raiseThreshold || ($street !== RoundStreet::PreFlop && $score >= $bluffThreshold))) {
            return new OpponentDecision(
                PokerAction::Raise,
                $this->raiseTarget($currentBet, $amountToCall, $botStack, $score >= 82 ? 90 : 50),
                $this->message('Bot agressivo aumentou pressionando', $difficulty, $label),
            );
        }

        if ($amountToCall <= 0) {
            return new OpponentDecision(PokerAction::Check, 0, $this->message('Bot agressivo aguardou', $difficulty, $label));
        }

        $maxPressureByStack = (int) floor($botStack * ($difficulty === 'hard' ? 0.36 : 0.30));

        if ($amountToCall <= min($pressureLimit + $score, $maxPressureByStack)) {
            return new OpponentDecision(
                PokerAction::Call,
                min($amountToCall, $botStack),
                $this->message('Bot agressivo pagou', $difficulty, $label),
            );
        }

        return new OpponentDecision(PokerAction::Fold, 0, $this->message('Bot agressivo desistiu', $difficulty, $label));
    }

    private function raiseTarget(int $currentBet, int $amountToCall, int $botStack, int $pressure): int
    {
        $target = max($currentBet + 20, $currentBet + $pressure);

        return min($target, $currentBet + $amountToCall + $botStack);
    }

    private function message(string $action, string $difficulty, string $label): string
    {
        return sprintf('%s (%s) com %s.', $action, PokerBotProfiles::difficultyLabel($difficulty), $label);
    }
}
