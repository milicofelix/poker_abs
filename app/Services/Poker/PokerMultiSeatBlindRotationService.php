<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Support\Collection;

final class PokerMultiSeatBlindRotationService
{
    /**
     * @return Collection<int, PokerTablePlayer>
     */
    public function seatedPlayers(PokerTable $table): Collection
    {
        return $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->where(function ($query): void {
                $query->where('status', 'online')
                    ->orWhere('is_bot', true);
            })
            ->orderBy('seat_number')
            ->orderBy('id')
            ->get()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function positionsForNewHand(PokerTable $table): array
    {
        $players = $this->seatedPlayers($table);
        $seats = $players
            ->pluck('seat_number')
            ->map(static fn (mixed $seat): int => (int) $seat)
            ->filter(static fn (int $seat): bool => $seat > 0)
            ->values()
            ->all();

        if (count($seats) < 2) {
            $seat = $seats[0] ?? null;

            return [
                'dealerSeat' => $seat,
                'smallBlindSeat' => null,
                'bigBlindSeat' => null,
                'firstPreFlopSeat' => $seat,
                'firstPostFlopSeat' => $seat,
                'order' => $seats,
                'phase' => '12.7',
            ];
        }

        $previousDealerSeat = $this->previousDealerSeat($table);
        $dealerSeat = $previousDealerSeat === null
            ? $seats[0]
            : $this->nextSeatAfter($seats, $previousDealerSeat);

        if (count($seats) === 2) {
            $smallBlindSeat = $dealerSeat;
            $bigBlindSeat = $this->nextSeatAfter($seats, $dealerSeat);
            $firstPreFlopSeat = $dealerSeat;
            $firstPostFlopSeat = $bigBlindSeat;
        } else {
            $smallBlindSeat = $this->nextSeatAfter($seats, $dealerSeat);
            $bigBlindSeat = $this->nextSeatAfter($seats, $smallBlindSeat);
            $firstPreFlopSeat = $this->nextSeatAfter($seats, $bigBlindSeat);
            $firstPostFlopSeat = $this->nextSeatAfter($seats, $dealerSeat);
        }

        return [
            'dealerSeat' => $dealerSeat,
            'smallBlindSeat' => $smallBlindSeat,
            'bigBlindSeat' => $bigBlindSeat,
            'firstPreFlopSeat' => $firstPreFlopSeat,
            'firstPostFlopSeat' => $firstPostFlopSeat,
            'order' => $seats,
            'phase' => '12.7',
        ];
    }

    /**
     * @param array<int, int> $seats
     */
    public function nextSeatAfter(array $seats, int $seat): ?int
    {
        $seats = array_values(array_unique(array_map('intval', $seats)));
        sort($seats);

        if ($seats === []) {
            return null;
        }

        foreach ($seats as $candidate) {
            if ($candidate > $seat) {
                return $candidate;
            }
        }

        return $seats[0];
    }

    private function previousDealerSeat(PokerTable $table): ?int
    {
        /** @var PokerHand|null $hand */
        $hand = $table->hands()
            ->latest('id')
            ->first();

        if (! $hand) {
            return null;
        }

        $stateDealer = data_get($hand->state_payload, 'multiSeat.dealerSeat');

        return $stateDealer !== null
            ? (int) $stateDealer
            : (int) $hand->dealer_position;
    }
}
