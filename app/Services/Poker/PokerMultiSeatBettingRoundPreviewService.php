<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;

final class PokerMultiSeatBettingRoundPreviewService
{
    /**
     * @param array<int, int> $committedBySeat
     * @param array<int, bool> $actedBySeat
     * @return array<string, mixed>
     */
    public function preview(
        PokerTable $table,
        int $currentBet = 0,
        array $committedBySeat = [],
        array $actedBySeat = [],
    ): array {
        $players = $this->eligiblePlayers($table);
        $currentBet = max(0, $currentBet);
        $seatRounds = $players
            ->values()
            ->map(fn (PokerTablePlayer $player, int $index): array => $this->seatRoundPayload(
                $player,
                $index + 1,
                $currentBet,
                $committedBySeat,
                $actedBySeat,
            ))
            ->values()
            ->all();

        if ($seatRounds === []) {
            return $this->emptyPayload($table);
        }

        $pendingSeats = array_values(array_map(
            static fn (array $seat): int => (int) $seat['seatNumber'],
            array_filter($seatRounds, static fn (array $seat): bool => ! (bool) $seat['isResolved']),
        ));

        $resolvedSeats = array_values(array_map(
            static fn (array $seat): int => (int) $seat['seatNumber'],
            array_filter($seatRounds, static fn (array $seat): bool => (bool) $seat['isResolved']),
        ));

        return [
            'phase' => '10.6',
            'schemaVersion' => 'multi_seat_betting_round.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentBet' => $currentBet,
            'activeContenders' => count($seatRounds),
            'resolvedSeats' => $resolvedSeats,
            'pendingSeats' => $pendingSeats,
            'canCloseStreet' => count($seatRounds) > 1 && $pendingSeats === [],
            'shouldContinueHandImmediately' => count($seatRounds) > 1,
            'streetResolutionRule' => 'Todos os assentos ativos precisam ter agido e igualado a aposta atual, salvo all-in.',
            'seatRounds' => $seatRounds,
            'note' => 'Preview tecnico da rodada de apostas multi-seat; ainda nao fecha streets no motor principal.',
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
            ->orderByRaw('COALESCE(seat_number, 999999) asc')
            ->orderBy('id')
            ->get()
            ->filter(static fn (PokerTablePlayer $player): bool => (int) $player->stack > 0 || $player->status === 'all_in')
            ->values();
    }

    /**
     * @param array<int, int> $committedBySeat
     * @param array<int, bool> $actedBySeat
     * @return array<string, mixed>
     */
    private function seatRoundPayload(
        PokerTablePlayer $player,
        int $fallbackSeat,
        int $currentBet,
        array $committedBySeat,
        array $actedBySeat,
    ): array {
        $seat = (int) ($player->seat_number ?? $fallbackSeat);
        $committed = max(0, (int) ($committedBySeat[$seat] ?? 0));
        $stack = max(0, (int) $player->stack);
        $hasActed = (bool) ($actedBySeat[$seat] ?? false);
        $isAllIn = $stack === 0 || $player->status === 'all_in';
        $hasMatchedCurrentBet = $committed >= $currentBet;
        $isResolved = $isAllIn || ($hasActed && $hasMatchedCurrentBet);

        return [
            'tablePlayerId' => $player->id,
            'userId' => $player->user_id,
            'nickname' => $player->nickname,
            'seatNumber' => $seat,
            'status' => $player->status,
            'stack' => $stack,
            'committed' => $committed,
            'amountToCall' => max(0, $currentBet - $committed),
            'hasActed' => $hasActed,
            'hasMatchedCurrentBet' => $hasMatchedCurrentBet,
            'isAllIn' => $isAllIn,
            'isResolved' => $isResolved,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(PokerTable $table): array
    {
        return [
            'phase' => '10.6',
            'schemaVersion' => 'multi_seat_betting_round.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'currentBet' => 0,
            'activeContenders' => 0,
            'resolvedSeats' => [],
            'pendingSeats' => [],
            'canCloseStreet' => false,
            'shouldContinueHandImmediately' => false,
            'streetResolutionRule' => 'Nenhum assento ativo para avaliar fechamento de street.',
            'seatRounds' => [],
            'note' => 'Nenhum assento ativo para avaliar rodada de apostas multi-seat.',
        ];
    }
}
