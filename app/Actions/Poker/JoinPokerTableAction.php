<?php

namespace App\Actions\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use DomainException;

final class JoinPokerTableAction
{
    public function execute(PokerTable $table, User $user): PokerTablePlayer
    {
        $existingPlayer = PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $existingPlayer && $this->isTableFull($table)) {
            throw new DomainException('Mesa cheia. Escolha outra mesa no lobby.');
        }

        return PokerTablePlayer::query()->updateOrCreate(
            [
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
            ],
            [
                'nickname' => $user->name,
                'status' => 'online',
                'left_at' => null,
                'last_seen_at' => now(),
                'joined_at' => $existingPlayer?->joined_at ?? now(),
            ],
        );
    }

    private function isTableFull(PokerTable $table): bool
    {
        $activePlayers = $table->realPlayers()
            ->whereNull('left_at')
            ->count();

        return $activePlayers >= (int) $table->max_players;
    }
}
