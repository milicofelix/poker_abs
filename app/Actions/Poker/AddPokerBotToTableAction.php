<?php

namespace App\Actions\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerTableSeatCapacityService;
use App\Support\Poker\PokerBotProfiles;
use DomainException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AddPokerBotToTableAction
{
    public function __construct(private readonly PokerTableSeatCapacityService $seatCapacity)
    {
    }

    public function execute(PokerTable $table, string $profile, string $difficulty = 'normal', bool $replaceExistingBot = false): PokerTablePlayer
    {
        if (! in_array($profile, PokerBotProfiles::keys(), true)) {
            throw new DomainException('Perfil de bot inválido.');
        }

        if (! in_array($difficulty, PokerBotProfiles::difficulties(), true)) {
            throw new DomainException('Dificuldade de bot inválida.');
        }

        $seatNumber = $this->seatCapacity->firstAvailableSeat($table);

        if ($seatNumber === null && $replaceExistingBot) {
            $seatNumber = $this->releaseReplaceableBotSeat($table);
        }

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
                'stack' => PokerTable::DEFAULT_BUY_IN,
                'buy_in_amount' => PokerTable::DEFAULT_BUY_IN,
                'buy_in_paid_at' => now(),
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

    private function releaseReplaceableBotSeat(PokerTable $table): ?int
    {
        /** @var PokerTablePlayer|null $bot */
        $bot = $table->realPlayers()
            ->where('is_bot', true)
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->orderByDesc('seat_number')
            ->orderByDesc('id')
            ->first();

        if (! $bot) {
            return null;
        }

        $seatNumber = (int) $bot->seat_number;

        $bot->forceFill([
            'seat_number' => null,
            'status' => 'offline',
            'left_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        $table->unsetRelation('realPlayers');

        return $seatNumber;
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
