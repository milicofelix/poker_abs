<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Support\Carbon;

final class PokerTablePresenceService
{
    private const OFFLINE_AFTER_SECONDS = 45;

    public function markCurrentUserOnline(PokerTable $table, ?User $user): ?PokerTablePlayer
    {
        if (! $user) {
            $this->markStalePlayersOffline($table);

            return null;
        }

        /** @var PokerTablePlayer|null $player */
        $player = $table->realPlayers()
            ->where('user_id', $user->id)
            ->first();

        if (! $player) {
            $this->markStalePlayersOffline($table);

            return null;
        }

        $player->forceFill([
            'status' => 'online',
            'left_at' => null,
            'last_seen_at' => now(),
        ])->save();

        $this->markStalePlayersOffline($table);

        return $player->fresh();
    }

    public function leave(PokerTable $table, User $user): ?PokerTablePlayer
    {
        /** @var PokerTablePlayer|null $player */
        $player = $table->realPlayers()
            ->where('user_id', $user->id)
            ->first();

        if (! $player) {
            return null;
        }

        $player->forceFill([
            'status' => 'offline',
            'seat_number' => null,
            'left_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        return $player->fresh();
    }

    public function markStalePlayersOffline(PokerTable $table): void
    {
        $threshold = Carbon::now()->subSeconds(self::OFFLINE_AFTER_SECONDS);

        $table->realPlayers()
            ->where('status', 'online')
            ->where(function ($query) use ($threshold): void {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $threshold);
            })
            ->update(['status' => 'offline']);
    }
}
