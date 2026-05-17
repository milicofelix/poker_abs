<?php

namespace App\Actions\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Services\Poker\PokerTableSeatCapacityService;
use DomainException;

final class SitPokerTablePlayerAction
{
    public function __construct(private readonly PokerTableSeatCapacityService $seatCapacity)
    {
    }

    public function execute(PokerTable $table, PokerTablePlayer $player, int $seatNumber): PokerTablePlayer
    {
        if (! $this->seatCapacity->isSeatInsideDeclaredCapacity($table, $seatNumber)) {
            throw new DomainException('Assento inválido para esta mesa.');
        }

        if ($this->seatCapacity->isSeatOccupied($table, $seatNumber, $player)) {
            throw new DomainException('Este assento já está ocupado.');
        }

        $player->update([
            'seat_number' => $seatNumber,
        ]);

        return $player->fresh();
    }
}
