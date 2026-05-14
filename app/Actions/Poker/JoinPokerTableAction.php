<?php

namespace App\Actions\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;

final class JoinPokerTableAction
{
    public function execute(PokerTable $table, User $user): PokerTablePlayer
    {
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
                'joined_at' => now(),
            ],
        );
    }
}
