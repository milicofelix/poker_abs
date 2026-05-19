<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;

final class PokerMultiSeatActionPreviewService
{
    /**
     * @param array<int, int> $committedBySeat
     * @param array<int, bool> $actedBySeat
     * @return array<string, mixed>
     */
    public function preview(
        PokerTable $table,
        ?int $currentSeat = null,
        int $currentBet = 0,
        int $minimumRaise = 20,
        array $committedBySeat = [],
        array $actedBySeat = [],
    ): array {
        $players = $this->eligiblePlayers($table);
        $seats = $players
            ->values()
            ->map(static fn (PokerTablePlayer $player, int $index): int => (int) ($player->seat_number ?? ($index + 1)))
            ->values()
            ->all();

        if ($seats === []) {
            return $this->emptyPayload($table);
        }

        $currentSeat = $this->normalizeCurrentSeat($seats, $currentSeat);
        $currentPlayer = $players
            ->values()
            ->first(static fn (PokerTablePlayer $player, int $index): bool => (int) ($player->seat_number ?? ($index + 1)) === $currentSeat);
        $committed = max(0, (int) ($committedBySeat[$currentSeat] ?? 0));
        $stack = max(0, (int) ($currentPlayer?->stack ?? 0));
        $amountToCall = max(0, $currentBet - $committed);
        $minimumRaise = max((int) $table->big_blind, $minimumRaise, 1);
        $minimumRaiseTo = $currentBet + $minimumRaise;
        $maximumRaiseTo = $committed + $stack;

        return [
            'phase' => '10.5',
            'schemaVersion' => 'multi_seat_actions.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentSeat' => $currentSeat,
            'currentBet' => max(0, $currentBet),
            'minimumRaise' => $minimumRaise,
            'minimumRaiseTo' => $minimumRaiseTo,
            'maximumRaiseTo' => $maximumRaiseTo,
            'amountToCall' => $amountToCall,
            'canCheck' => $amountToCall === 0,
            'canCall' => $amountToCall > 0 && $stack > 0,
            'canRaise' => $stack > $amountToCall && $maximumRaiseTo >= $minimumRaiseTo,
            'canFold' => true,
            'allActionsMapped' => true,
            'streetIsComplete' => $this->streetIsComplete($players, max(0, $currentBet), $committedBySeat, $actedBySeat),
            'seatActions' => $players
                ->map(fn (PokerTablePlayer $player, int $index): array => $this->seatActionPayload(
                    $player,
                    $index + 1,
                    max(0, $currentBet),
                    $minimumRaise,
                    $committedBySeat,
                    $actedBySeat,
                    $currentSeat,
                ))
                ->values()
                ->all(),
            'note' => 'Preview tecnico das acoes multi-seat; ainda nao substitui fold/check/call/raise do motor heads-up.',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, PokerTablePlayer>
     */
    private function eligiblePlayers(PokerTable $table)
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
     * @param array<int, int> $seats
     */
    private function normalizeCurrentSeat(array $seats, ?int $currentSeat): int
    {
        sort($seats);

        if ($currentSeat !== null && in_array($currentSeat, $seats, true)) {
            return $currentSeat;
        }

        return $seats[0];
    }

    /**
     * @param array<int, int> $committedBySeat
     * @param array<int, bool> $actedBySeat
     * @return array<string, mixed>
     */
    private function seatActionPayload(
        PokerTablePlayer $player,
        int $fallbackSeat,
        int $currentBet,
        int $minimumRaise,
        array $committedBySeat,
        array $actedBySeat,
        int $currentSeat,
    ): array {
        $seat = (int) ($player->seat_number ?? $fallbackSeat);
        $committed = max(0, (int) ($committedBySeat[$seat] ?? 0));
        $stack = max(0, (int) $player->stack);
        $amountToCall = max(0, $currentBet - $committed);
        $minimumRaiseTo = $currentBet + $minimumRaise;
        $maximumRaiseTo = $committed + $stack;

        return [
            'tablePlayerId' => $player->id,
            'userId' => $player->user_id,
            'nickname' => $player->nickname,
            'seatNumber' => $seat,
            'isCurrentSeat' => $seat === $currentSeat,
            'status' => $player->status,
            'stack' => $stack,
            'committed' => $committed,
            'amountToCall' => $amountToCall,
            'hasActed' => (bool) ($actedBySeat[$seat] ?? false),
            'canCheck' => $amountToCall === 0,
            'canCall' => $amountToCall > 0 && $stack > 0,
            'canRaise' => $stack > $amountToCall && $maximumRaiseTo >= $minimumRaiseTo,
            'canFold' => true,
        ];
    }

    /**
     * @param array<int, int> $committedBySeat
     * @param array<int, bool> $actedBySeat
     */
    private function streetIsComplete($players, int $currentBet, array $committedBySeat, array $actedBySeat): bool
    {
        if ($players->isEmpty()) {
            return false;
        }

        foreach ($players->values() as $index => $player) {
            $seat = (int) ($player->seat_number ?? ($index + 1));
            $committed = max(0, (int) ($committedBySeat[$seat] ?? 0));
            $stack = max(0, (int) $player->stack);
            $hasActed = (bool) ($actedBySeat[$seat] ?? false);
            $hasMatchedBet = $committed >= $currentBet || $stack === 0;

            if (! $hasActed || ! $hasMatchedBet) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(PokerTable $table): array
    {
        return [
            'phase' => '10.5',
            'schemaVersion' => 'multi_seat_actions.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentSeat' => null,
            'currentBet' => 0,
            'minimumRaise' => max(1, (int) $table->big_blind),
            'minimumRaiseTo' => 0,
            'maximumRaiseTo' => 0,
            'amountToCall' => 0,
            'canCheck' => false,
            'canCall' => false,
            'canRaise' => false,
            'canFold' => false,
            'allActionsMapped' => false,
            'streetIsComplete' => false,
            'seatActions' => [],
            'note' => 'Nenhum assento ativo para mapear acoes multi-seat.',
        ];
    }
}
