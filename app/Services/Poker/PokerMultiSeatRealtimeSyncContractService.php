<?php

namespace App\Services\Poker;

final class PokerMultiSeatRealtimeSyncContractService
{
    public const PHASE = '10.16';
    public const SCHEMA_VERSION = 'multi_seat_realtime_sync.v0';

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
                'mode' => 'heads_up_broadcast',
                'requiresHydration' => true,
                'message' => 'Realtime multi-seat fica inativo quando a mesa usa o motor heads-up.',
            ];
        }

        $players = collect(data_get($state, 'multiSeat.players', []))
            ->filter(static fn (mixed $player): bool => is_array($player))
            ->values();

        $activeSeatNumbers = $players
            ->filter(static fn (array $player): bool => ! (bool) ($player['hasFolded'] ?? false))
            ->pluck('seatNumber')
            ->filter(static fn (mixed $seat): bool => $seat !== null)
            ->map(static fn (mixed $seat): int => (int) $seat)
            ->values()
            ->all();

        $currentSeat = data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber'));
        $isFinished = (bool) ($state['isFinished'] ?? false);

        return [
            'phase' => self::PHASE,
            'schemaVersion' => self::SCHEMA_VERSION,
            'enabled' => true,
            'mode' => 'multi_seat_lightweight_broadcast',
            'requiresHydration' => true,
            'hydrationReason' => 'Broadcast envia apenas sinal leve; cada cliente reidrata sua visão privada da mesa.',
            'syncVersion' => data_get($state, 'persistence.syncVersion'),
            'tableId' => data_get($state, 'persistence.tableId'),
            'street' => data_get($state, 'street'),
            'isFinished' => $isFinished,
            'currentSeat' => $isFinished ? null : ($currentSeat === null ? null : (int) $currentSeat),
            'activeSeatNumbers' => $activeSeatNumbers,
            'activePlayerCount' => count($activeSeatNumbers),
            'totalPlayerCount' => $players->count(),
            'winnerSeats' => collect(data_get($state, 'multiSeat.winnerSeats', []))
                ->map(static fn (mixed $seat): int => (int) $seat)
                ->values()
                ->all(),
            'message' => $isFinished
                ? 'Mão multi-seat finalizada; clientes devem reidratar conclusão privada.'
                : 'Estado multi-seat atualizado; clientes devem buscar a visão privada mais recente.',
        ];
    }
}
