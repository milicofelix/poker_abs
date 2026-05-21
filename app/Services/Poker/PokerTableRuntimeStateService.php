<?php

namespace App\Services\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Models\Poker\PokerTable;

final class PokerTableRuntimeStateService
{
    public function __construct(
        private readonly PokerTableReadinessService $readiness,
        private readonly PokerTableStateContractService $stateContracts,
    ) {
    }

    /**
     * Resolve o estado runtime da mesa usando os contratos como limite interno.
     *
     * Nesta fase, o contrato heads-up continua sendo a fonte oficial do jogo.
     * O contrato multi-seat é apenas descritivo/preparatório e não altera o
     * comportamento visual, as ações, o timer ou o fluxo de nova mão.
     *
     * @return array{state: array<string, mixed>, stateContracts: array<string, mixed>, engineMode: string, multiSeatEnabled: bool}
     */
    public function resolve(
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
        StartPokerHandAction $startPokerHand,
    ): array {
        $state = $pokerPersistence->latestStateForTable($table)
            ?? $this->readiness->startIfReady($table, $startPokerHand, $pokerPersistence);

        $stateContracts = $this->stateContracts->forTable($table, $state);

        return [
            'state' => $state,
            'stateContracts' => $stateContracts,
            'engineMode' => (string) ($stateContracts['active'] ?? 'heads_up'),
            'multiSeatEnabled' => (bool) ($stateContracts['compatibility']['multiSeatEnabled'] ?? false),
        ];
    }
}
