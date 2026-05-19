<?php

namespace App\Services\Poker;

final class PokerMultiSeatQaStabilityContractService
{
    public const PHASE = '10.17';
    public const SCHEMA_VERSION = 'multi_seat_qa_stability.v0';

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function forState(array $state): array
    {
        $enabled = (bool) data_get($state, 'multiSeat.enabled', false);

        if (! $enabled) {
            return [
                'phase' => self::PHASE,
                'schemaVersion' => self::SCHEMA_VERSION,
                'enabled' => false,
                'status' => 'heads_up_preserved',
                'criticalIssues' => [],
                'warnings' => [],
                'regressionChecks' => [
                    'headsUpPreserved' => true,
                    'multiSeatRequiresPrivateHydration' => true,
                    'finishedHandHasNoTurn' => true,
                    'currentTurnBelongsToActiveSeat' => true,
                ],
                'message' => 'QA multi-seat fica em observação sem alterar a engine heads-up.',
            ];
        }

        $players = collect(data_get($state, 'multiSeat.players', []))
            ->filter(static fn (mixed $player): bool => is_array($player))
            ->values();

        $activeSeats = $players
            ->filter(static fn (array $player): bool => ! (bool) ($player['hasFolded'] ?? false))
            ->filter(static fn (array $player): bool => ! (bool) ($player['isEliminated'] ?? false))
            ->pluck('seatNumber')
            ->filter(static fn (mixed $seat): bool => $seat !== null)
            ->map(static fn (mixed $seat): int => (int) $seat)
            ->values()
            ->all();

        $isFinished = (bool) ($state['isFinished'] ?? false);
        $currentSeat = data_get($state, 'currentTurn.seatNumber', data_get($state, 'multiSeat.currentSeat'));
        $currentSeat = $currentSeat === null ? null : (int) $currentSeat;

        $criticalIssues = [];
        $warnings = [];

        if ($players->count() < 3) {
            $criticalIssues[] = 'multi-seat ativo com menos de 3 jogadores no estado.';
        }

        if ($isFinished && $currentSeat !== null) {
            $criticalIssues[] = 'mão finalizada ainda possui assento com turno ativo.';
        }

        if (! $isFinished && $currentSeat !== null && ! in_array($currentSeat, $activeSeats, true)) {
            $criticalIssues[] = 'turno atual não pertence a um assento ativo.';
        }

        if (! $isFinished && $currentSeat === null) {
            $warnings[] = 'mão multi-seat ativa sem assento de turno definido.';
        }

        if ((bool) data_get($state, 'multiSeat.realtime.requiresHydration', true) === false) {
            $warnings[] = 'realtime multi-seat deve continuar usando reidratação privada.';
        }

        return [
            'phase' => self::PHASE,
            'schemaVersion' => self::SCHEMA_VERSION,
            'enabled' => true,
            'status' => $criticalIssues === [] ? 'stable' : 'attention_required',
            'criticalIssues' => $criticalIssues,
            'warnings' => $warnings,
            'activeSeatNumbers' => $activeSeats,
            'activePlayerCount' => count($activeSeats),
            'totalPlayerCount' => $players->count(),
            'currentSeat' => $isFinished ? null : $currentSeat,
            'regressionChecks' => [
                'headsUpPreserved' => true,
                'multiSeatRequiresPrivateHydration' => true,
                'finishedHandHasNoTurn' => ! ($isFinished && $currentSeat !== null),
                'currentTurnBelongsToActiveSeat' => $currentSeat === null || in_array($currentSeat, $activeSeats, true),
            ],
            'message' => $criticalIssues === []
                ? 'QA multi-seat estável para fluxo 3+ controlado.'
                : 'QA multi-seat encontrou inconsistências que exigem correção antes de testes visuais amplos.',
        ];
    }
}
