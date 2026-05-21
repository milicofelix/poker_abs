<?php

namespace App\Services\Poker;

use App\Data\Poker\HeadsUpPokerTableStateContract;
use App\Data\Poker\MultiSeatPokerTableStateContract;
use App\Models\Poker\PokerTable;

final class PokerTableStateContractService
{
    public function __construct(
        private readonly PokerMultiSeatEnginePreparationService $multiSeatPreparation,
        private readonly PokerMultiSeatDealPreviewService $dealPreview,
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
            $this->multiSeatPreparation->preparationPayload($table, dealPreview: $this->dealPreview->preview($table)),
        );

        $activation = (new PokerMultiSeatEngineActivationService())->payload($table);
        $multiSeatStateEnabled = (bool) data_get($state, 'multiSeat.enabled', false);

        $qaStability = (new PokerMultiSeatQaStabilityContractService())->forState($state);

        return [
            'active' => $multiSeatStateEnabled ? 'multi_seat' : 'heads_up',
            'next' => $table->isMultiSeatCandidate() && ! $multiSeatStateEnabled ? 'multi_seat' : null,
            'headsUp' => $headsUp->toArray(),
            'multiSeat' => [
                ...$multiSeat->toArray(),
                'activation' => $activation,
            ],
            'qaStability' => $qaStability,
            'compatibility' => [
                'headsUpStillAuthoritative' => ! $multiSeatStateEnabled,
                'multiSeatEnabled' => $multiSeatStateEnabled,
                'canStartThreePlusPlayers' => $multiSeatStateEnabled,
                'message' => $multiSeatStateEnabled
                    ? 'Motor multi-seat controlado está ativo para esta mesa 3+.'
                    : '3+ jogadores ainda estão em preparação; o contrato heads-up segue como fonte de verdade.',
            ],
        ];
    }
}
