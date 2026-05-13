<?php

namespace App\Domain\Poker\Game;

final class SimpleOpponentStrategy
{
    public function decide(RoundStreet $street, int $currentBet, int $opponentStack): OpponentDecision
    {
        if ($opponentStack <= 0) {
            return new OpponentDecision(
                action: PokerAction::Check,
                amount: 0,
                message: 'Oponente sem fichas disponíveis.',
            );
        }

        if ($street === RoundStreet::River && $currentBet >= 80) {
            return new OpponentDecision(
                action: PokerAction::Fold,
                amount: 0,
                message: 'Oponente desistiu após pressão no river.',
            );
        }

        if ($currentBet > 0) {
            $amount = min($currentBet, $opponentStack);

            return new OpponentDecision(
                action: PokerAction::Call,
                amount: $amount,
                message: 'Oponente pagou a aposta.',
            );
        }

        return new OpponentDecision(
            action: PokerAction::Check,
            amount: 0,
            message: 'Oponente pediu mesa.',
        );
    }
}
