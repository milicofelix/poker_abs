<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Support\Collection;
use App\Services\Poker\PokerMultiSeatEnginePreparationService;

final class PokerTableSeatCapacityService
{
    public function __construct(
        private readonly PokerMultiSeatEnginePreparationService $multiSeatEnginePreparation = new PokerMultiSeatEnginePreparationService(),
    ) {
    }
    /**
     * @return Collection<int, int>
     */
    public function occupiedSeatNumbers(PokerTable $table): Collection
    {
        return $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->pluck('seat_number')
            ->map(static fn (mixed $seat): int => (int) $seat)
            ->filter(static fn (int $seat): bool => $seat > 0)
            ->unique()
            ->values();
    }

    public function isSeatInsideDeclaredCapacity(PokerTable $table, int $seatNumber): bool
    {
        return $seatNumber >= 1 && $seatNumber <= $table->declaredMaxPlayers();
    }

    public function isSeatOccupied(PokerTable $table, int $seatNumber, ?PokerTablePlayer $exceptPlayer = null): bool
    {
        $query = $table->realPlayers()
            ->where('seat_number', $seatNumber)
            ->whereNull('left_at');

        if ($exceptPlayer !== null && $exceptPlayer->exists) {
            $query->where('id', '!=', $exceptPlayer->id);
        }

        return $query->exists();
    }

    public function firstAvailableSeat(PokerTable $table): ?int
    {
        $occupiedSeats = $this->occupiedSeatNumbers($table)->all();

        for ($seatNumber = 1; $seatNumber <= $table->declaredMaxPlayers(); $seatNumber++) {
            if (! in_array($seatNumber, $occupiedSeats, true)) {
                return $seatNumber;
            }
        }

        return null;
    }

    public function activePlayersCount(PokerTable $table): int
    {
        return $table->realPlayers()
            ->whereNull('left_at')
            ->count();
    }

    public function hasRoomForAnotherPlayer(PokerTable $table): bool
    {
        return $this->activePlayersCount($table) < $table->declaredMaxPlayers();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(PokerTable $table): array
    {
        $occupiedSeats = $this->occupiedSeatNumbers($table);

        return [
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentEngineMaxPlayers' => $table->currentEngineMaxPlayers(),
            'activePlayers' => $this->activePlayersCount($table),
            'occupiedSeats' => $occupiedSeats->all(),
            'availableSeats' => max(0, $table->declaredMaxPlayers() - $occupiedSeats->count()),
            'firstAvailableSeat' => $this->firstAvailableSeat($table),
            'canAcceptPlayers' => $this->hasRoomForAnotherPlayer($table),
            'engineMode' => $table->engineMode(),
            'isMultiSeatCandidate' => $table->isMultiSeatCandidate(),
            'multiSeatEnginePreparation' => $this->multiSeatEnginePreparation->preparationPayload($table),
        ];
    }
}
