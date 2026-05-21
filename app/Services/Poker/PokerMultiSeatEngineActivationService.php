<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;

final class PokerMultiSeatEngineActivationService
{
    public const PHASE = '10.9';
    public const MINIMUM_PLAYERS = 3;

    public function isEnabledFor(PokerTable $table): bool
    {
        return $table->isMultiSeatCandidate()
            && $this->activeSeatedPlayers($table) >= self::MINIMUM_PLAYERS;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(PokerTable $table): array
    {
        $activeSeatedPlayers = $this->activeSeatedPlayers($table);
        $enabled = $this->isEnabledFor($table);

        return [
            'phase' => self::PHASE,
            'enabled' => $enabled,
            'mode' => $enabled ? 'multi_seat_controlled' : 'heads_up_safe',
            'minimumPlayers' => self::MINIMUM_PLAYERS,
            'activeSeatedPlayers' => $activeSeatedPlayers,
            'playersNeeded' => max(0, self::MINIMUM_PLAYERS - $activeSeatedPlayers),
            'maxPlayers' => $table->declaredMaxPlayers(),
            'message' => $enabled
                ? 'Motor multi-seat ativado de forma controlada para mesas com 3+ jogadores sentados.'
                : 'Motor heads-up preservado até a mesa ter 3+ jogadores sentados.',
        ];
    }

    private function activeSeatedPlayers(PokerTable $table): int
    {
        return $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->where(function ($query): void {
                $query->where('status', 'online')
                    ->orWhere('is_bot', true);
            })
            ->count();
    }
}
