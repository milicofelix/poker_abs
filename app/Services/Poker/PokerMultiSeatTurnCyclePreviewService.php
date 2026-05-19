<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;

final class PokerMultiSeatTurnCyclePreviewService
{
    /**
     * @return array<string, mixed>
     */
    public function preview(PokerTable $table, ?int $dealerSeat = null, ?int $currentSeat = null): array
    {
        $players = $this->activePlayers($table);
        $order = $players
            ->values()
            ->map(fn (PokerTablePlayer $player, int $index): array => $this->serializePlayer($player, $index + 1))
            ->values()
            ->all();
        $seatNumbers = array_column($order, 'seatNumber');

        if ($order === []) {
            return $this->emptyPayload();
        }

        $dealerSeat ??= $this->firstExistingSeatAtOrAfter($seatNumbers, (int) ($dealerSeat ?? $seatNumbers[0]));
        $blinds = $this->blindPreview($seatNumbers, $dealerSeat);
        $currentSeat ??= $this->firstSeatAfter($seatNumbers, (int) ($blinds['bigBlindSeat'] ?? $dealerSeat), $order);

        return [
            'phase' => '10.4',
            'schemaVersion' => 'multi_seat_turn_cycle.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'activeSeatedPlayers' => count($order),
            'dealerSeat' => $dealerSeat,
            'smallBlindSeat' => $blinds['smallBlindSeat'],
            'bigBlindSeat' => $blinds['bigBlindSeat'],
            'currentSeat' => $currentSeat,
            'nextSeatToAct' => $this->firstSeatAfter($seatNumbers, (int) $currentSeat, $order),
            'firstPreFlopSeat' => $this->firstSeatAfter($seatNumbers, (int) ($blinds['bigBlindSeat'] ?? $dealerSeat), $order),
            'firstPostFlopSeat' => $this->firstSeatAfter($seatNumbers, (int) $dealerSeat, $order),
            'actionOrder' => $this->actionOrder($order, (int) $currentSeat),
            'skippedSeats' => [],
            'note' => 'Preview tecnico da fila circular multi-seat; ainda nao executa jogadas no motor principal.',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, PokerTablePlayer>
     */
    private function activePlayers(PokerTable $table)
    {
        return $table->realPlayers()
            ->whereNull('left_at')
            ->whereNotIn('status', ['folded', 'left', 'out'])
            ->orderByRaw('seat_number IS NULL')
            ->orderBy('seat_number')
            ->orderBy('id')
            ->get()
            ->filter(static fn (PokerTablePlayer $player): bool => (int) $player->stack > 0)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlayer(PokerTablePlayer $player, int $fallbackSeat): array
    {
        $seatNumber = (int) ($player->seat_number ?? $fallbackSeat);

        return [
            'tablePlayerId' => $player->id,
            'userId' => $player->user_id,
            'nickname' => $player->nickname,
            'seatNumber' => $seatNumber,
            'status' => $player->status,
            'stack' => (int) $player->stack,
            'isBot' => (bool) $player->is_bot,
            'canAct' => true,
        ];
    }

    /**
     * @param array<int, int> $seatNumbers
     * @return array{smallBlindSeat: int|null, bigBlindSeat: int|null}
     */
    private function blindPreview(array $seatNumbers, int $dealerSeat): array
    {
        $count = count($seatNumbers);

        if ($count < 2) {
            return ['smallBlindSeat' => null, 'bigBlindSeat' => null];
        }

        if ($count === 2) {
            return [
                'smallBlindSeat' => $dealerSeat,
                'bigBlindSeat' => $this->firstSeatAfter($seatNumbers, $dealerSeat),
            ];
        }

        $smallBlind = $this->firstSeatAfter($seatNumbers, $dealerSeat);

        return [
            'smallBlindSeat' => $smallBlind,
            'bigBlindSeat' => $this->firstSeatAfter($seatNumbers, (int) $smallBlind),
        ];
    }

    /**
     * @param array<int, int> $seatNumbers
     * @param array<int, array<string, mixed>>|null $order
     */
    private function firstSeatAfter(array $seatNumbers, int $seat, ?array $order = null): ?int
    {
        if ($seatNumbers === []) {
            return null;
        }

        sort($seatNumbers);
        $candidateSeats = $order === null
            ? $seatNumbers
            : array_values(array_map(static fn (array $player): int => (int) $player['seatNumber'], array_filter(
                $order,
                static fn (array $player): bool => (bool) ($player['canAct'] ?? false),
            )));

        if ($candidateSeats === []) {
            return null;
        }

        sort($candidateSeats);

        foreach ($candidateSeats as $candidateSeat) {
            if ($candidateSeat > $seat) {
                return $candidateSeat;
            }
        }

        return $candidateSeats[0];
    }

    /**
     * @param array<int, int> $seatNumbers
     */
    private function firstExistingSeatAtOrAfter(array $seatNumbers, int $seat): int
    {
        sort($seatNumbers);

        foreach ($seatNumbers as $candidateSeat) {
            if ($candidateSeat >= $seat) {
                return $candidateSeat;
            }
        }

        return $seatNumbers[0];
    }

    /**
     * @param array<int, array<string, mixed>> $order
     * @return array<int, array<string, mixed>>
     */
    private function actionOrder(array $order, int $currentSeat): array
    {
        $seatNumbers = array_map(static fn (array $player): int => (int) $player['seatNumber'], $order);
        $nextSeat = $currentSeat;
        $result = [];

        for ($position = 1; $position <= count($order); $position++) {
            $nextSeat = $this->firstSeatAfter($seatNumbers, (int) $nextSeat, $order);
            $player = collect($order)->first(static fn (array $item): bool => (int) $item['seatNumber'] === (int) $nextSeat);

            if (! is_array($player)) {
                continue;
            }

            $result[] = array_merge($player, [
                'actionPosition' => $position,
                'isCurrentSeat' => (int) $player['seatNumber'] === $currentSeat,
            ]);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'phase' => '10.4',
            'schemaVersion' => 'multi_seat_turn_cycle.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => 2,
            'activeSeatedPlayers' => 0,
            'dealerSeat' => null,
            'smallBlindSeat' => null,
            'bigBlindSeat' => null,
            'currentSeat' => null,
            'nextSeatToAct' => null,
            'firstPreFlopSeat' => null,
            'firstPostFlopSeat' => null,
            'actionOrder' => [],
            'skippedSeats' => [],
            'note' => 'Nenhum assento ativo para montar fila circular.',
        ];
    }
}
