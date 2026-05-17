<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Support\Collection;

final class PokerMultiSeatEnginePreparationService
{
    /**
     * @return Collection<int, PokerTablePlayer>
     */
    public function activeSeatedPlayers(PokerTable $table): Collection
    {
        return $table->realPlayers()
            ->whereNull('left_at')
            ->whereNotNull('seat_number')
            ->orderBy('seat_number')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function turnOrder(PokerTable $table, ?int $dealerSeat = null): array
    {
        $players = $this->activeSeatedPlayers($table)->values();

        if ($players->isEmpty()) {
            return [];
        }

        $dealerSeat ??= (int) ($players->first()?->seat_number ?? 1);
        $dealerIndex = $players->search(static fn (PokerTablePlayer $player): bool => (int) $player->seat_number >= $dealerSeat);

        if ($dealerIndex === false) {
            $dealerIndex = 0;
        }

        return $players
            ->slice((int) $dealerIndex)
            ->concat($players->slice(0, (int) $dealerIndex))
            ->values()
            ->map(static fn (PokerTablePlayer $player): array => [
                'tablePlayerId' => $player->id,
                'userId' => $player->user_id,
                'nickname' => $player->nickname,
                'seatNumber' => (int) $player->seat_number,
                'isBot' => (bool) $player->is_bot,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function blindSeats(PokerTable $table, ?int $dealerSeat = null): array
    {
        $order = $this->turnOrder($table, $dealerSeat);
        $count = count($order);

        if ($count < 2) {
            return [
                'dealerSeat' => $order[0]['seatNumber'] ?? null,
                'smallBlindSeat' => null,
                'bigBlindSeat' => null,
                'isReadyForMultiSeatBlinds' => false,
            ];
        }

        if ($count === 2) {
            return [
                'dealerSeat' => $order[0]['seatNumber'],
                'smallBlindSeat' => $order[0]['seatNumber'],
                'bigBlindSeat' => $order[1]['seatNumber'],
                'isReadyForMultiSeatBlinds' => false,
            ];
        }

        return [
            'dealerSeat' => $order[0]['seatNumber'],
            'smallBlindSeat' => $order[1]['seatNumber'],
            'bigBlindSeat' => $order[2]['seatNumber'],
            'isReadyForMultiSeatBlinds' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function preparationPayload(PokerTable $table, ?int $dealerSeat = null): array
    {
        $order = $this->turnOrder($table, $dealerSeat);

        return [
            'engineMode' => $table->engineMode(),
            'isMultiSeatCandidate' => $table->isMultiSeatCandidate(),
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentEngineMaxPlayers' => $table->currentEngineMaxPlayers(),
            'activeSeatedPlayers' => count($order),
            'turnOrder' => $order,
            'blinds' => $this->blindSeats($table, $dealerSeat),
            'isPlayableByCurrentEngine' => count($order) >= $table->minimumPlayersToStartCurrentEngine(),
            'isMultiSeatEngineEnabled' => false,
            'note' => 'Base multi-seat preparada; motor de jogo 3+ ainda não ativado.',
        ];
    }
}
