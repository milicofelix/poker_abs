<?php

namespace App\Actions\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Support\Poker\PokerBotProfiles;
use DomainException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AddPokerBotToTableAction
{
    public function execute(PokerTable $table, string $profile, string $difficulty = 'normal'): PokerTablePlayer
    {
        if (! in_array($profile, PokerBotProfiles::keys(), true)) {
            throw new DomainException('Perfil de bot inválido.');
        }

        if (! in_array($difficulty, PokerBotProfiles::difficulties(), true)) {
            throw new DomainException('Dificuldade de bot inválida.');
        }

        $seatNumber = $this->firstAvailableSeat($table);

        if ($seatNumber === null) {
            throw new DomainException('Não existe assento livre para adicionar um bot nesta mesa.');
        }

        $botUser = $this->botUser($profile, $difficulty);

        return PokerTablePlayer::query()->updateOrCreate(
            [
                'poker_table_id' => $table->id,
                'user_id' => $botUser->id,
            ],
            [
                'nickname' => sprintf('Bot %s', PokerBotProfiles::label($profile)),
                'stack' => 1000,
                'seat_number' => $seatNumber,
                'status' => 'online',
                'is_bot' => true,
                'bot_profile' => $profile,
                'bot_difficulty' => $difficulty,
                'left_at' => null,
                'last_seen_at' => now(),
                'joined_at' => now(),
            ],
        )->fresh();
    }

    private function firstAvailableSeat(PokerTable $table): ?int
    {
        $occupiedSeats = $table->realPlayers()
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->map(static fn (mixed $seat): int => (int) $seat)
            ->all();

        for ($seat = 1; $seat <= (int) $table->max_players; $seat++) {
            if (! in_array($seat, $occupiedSeats, true)) {
                return $seat;
            }
        }

        return null;
    }

    private function botUser(string $profile, string $difficulty): User
    {
        $key = Str::slug("{$profile}-{$difficulty}");

        return User::query()->firstOrCreate(
            ['email' => "poker-bot-{$key}@poker-abs.local"],
            [
                'name' => sprintf('Bot %s %s', PokerBotProfiles::label($profile), PokerBotProfiles::difficultyLabel($difficulty)),
                'password' => Hash::make(Str::random(40)),
            ],
        );
    }
}
