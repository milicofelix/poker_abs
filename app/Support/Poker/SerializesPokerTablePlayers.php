<?php

namespace App\Support\Poker;

use App\Models\Poker\PokerTable;
use App\Support\Poker\PokerBotProfiles;
use Illuminate\Support\Collection;

trait SerializesPokerTablePlayers
{
    /**
     * @return array<string, mixed>
     */
    private function serializeTablePlayer($player): array
    {
        return [
            'id' => $player->id,
            'userId' => $player->user_id,
            'nickname' => $player->nickname,
            'stack' => $player->stack,
            'status' => $player->status,
            'seatNumber' => $player->seat_number,
            'lastSeenAt' => $player->last_seen_at?->toIso8601String(),
            'isBot' => (bool) ($player->is_bot ?? false),
            'botProfile' => $player->bot_profile,
            'botDifficulty' => $player->bot_difficulty,
            'botDifficultyLabel' => $player->bot_difficulty ? PokerBotProfiles::difficultyLabel((string) $player->bot_difficulty) : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeRealPlayers(PokerTable $table): array
    {
        return $table->realPlayers()
            ->orderByRaw('seat_number IS NULL')
            ->orderBy('seat_number')
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($player): array => $this->serializeTablePlayer($player))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeSeatSlots(PokerTable $table): array
    {
        $playersBySeat = collect($this->serializeRealPlayers($table))
            ->filter(static fn (array $player): bool => $player['seatNumber'] !== null)
            ->keyBy('seatNumber');

        return Collection::times((int) $table->max_players, static function (int $seatNumber) use ($playersBySeat): array {
            $player = $playersBySeat->get($seatNumber);

            return [
                'seatNumber' => $seatNumber,
                'status' => $player ? 'occupied' : 'available',
                'player' => $player,
            ];
        })->values()->all();
    }
}
