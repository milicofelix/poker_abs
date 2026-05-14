<?php

namespace App\Actions\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use DomainException;

final class SitPokerTablePlayerAction
{
    public function execute(PokerTable $table, PokerTablePlayer $player, int $seatNumber): PokerTablePlayer
    {
        if ($seatNumber < 1 || $seatNumber > $table->max_players) {
            throw new DomainException('Assento inválido para esta mesa.');
        }

        $seatOccupied = PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', $seatNumber)
            ->where('id', '!=', $player->id)
            ->exists();

        if ($seatOccupied) {
            throw new DomainException('Este assento já está ocupado.');
        }

        $player->update([
            'seat_number' => $seatNumber,
        ]);

        return $player->fresh();
    }
}
