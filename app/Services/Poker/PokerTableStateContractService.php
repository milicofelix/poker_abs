<?php

namespace App\Services\Poker;

use App\Data\Poker\HeadsUpPokerTableStateContract;
use App\Data\Poker\MultiSeatPokerTableStateContract;
use App\Models\Poker\PokerTable;

final class PokerTableStateContractService
{
    public function __construct(
        private readonly PokerMultiSeatEnginePreparationService $multiSeatPreparation,
    ) {
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function forTable(PokerTable $table, array $state): array
    {
        $headsUp = HeadsUpPokerTableStateContract::fromTableState($table, $state);
        $multiSeat = MultiSeatPokerTableStateContract::fromPreparationPayload(
            $table,
            $this->multiSeatPreparation->preparationPayload($table),
        );

        return [
            'active' => 'heads_up',
            'next' => $table->isMultiSeatCandidate() ? 'multi_seat' : null,
            'headsUp' => $headsUp->toArray(),
            'multiSeat' => $multiSeat->toArray(),
            'compatibility' => [
                'headsUpStillAuthoritative' => true,
                'multiSeatEnabled' => false,
                'canStartThreePlusPlayers' => false,
                'message' => '3+ jogadores ainda estão em preparação; o contrato heads-up segue como fonte de verdade.',
            ],
        ];
    }
}
