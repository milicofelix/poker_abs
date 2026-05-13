<?php

namespace App\Services\Poker;

use App\Data\Poker\HandConclusion;
use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;

final class ResolveLocalHandConclusion
{
    /**
     * @param array<string, mixed>|null $winner
     */
    public function execute(PokerAction $playerAction, ?PokerAction $opponentAction, RoundStreet $nextStreet, ?array $winner = null): HandConclusion
    {
        if ($playerAction === PokerAction::Fold) {
            return new HandConclusion(
                isFinished: true,
                reason: 'player_fold',
                message: 'Você desistiu da mão. O oponente venceu o pote.',
                winner: [
                    'player' => 'opponent',
                    'label' => 'Oponente',
                    'handName' => 'Vitória por desistência',
                ],
            );
        }

        if ($opponentAction === PokerAction::Fold) {
            return new HandConclusion(
                isFinished: true,
                reason: 'opponent_fold',
                message: 'O oponente desistiu. Você venceu o pote.',
                winner: [
                    'player' => 'player',
                    'label' => 'Você',
                    'handName' => 'Vitória por desistência',
                ],
            );
        }

        if ($nextStreet === RoundStreet::Showdown) {
            return new HandConclusion(
                isFinished: true,
                reason: 'showdown',
                message: $this->showdownMessage($winner),
                winner: $winner,
            );
        }

        return new HandConclusion(
            isFinished: false,
            reason: 'in_progress',
            message: 'A mão continua em andamento.',
        );
    }
    /**
     * @param array<string, mixed>|null $winner
     */
    private function showdownMessage(?array $winner): string
    {
        if ($winner === null) {
            return 'A mão chegou ao showdown, mas ainda não há cartas suficientes para definir o vencedor.';
        }

        if (($winner['player'] ?? null) === 'tie') {
            return sprintf('Showdown finalizado: empate com %s.', $winner['handName'] ?? 'a mesma mão');
        }

        return sprintf(
            'Showdown finalizado: %s venceu com %s.',
            $winner['label'] ?? 'Jogador',
            $winner['handName'] ?? 'a melhor mão',
        );
    }
}
