<?php

namespace App\Services\Poker;

use Illuminate\Support\Carbon;

final class PokerTurnTimerService
{
    private const DEFAULT_SECONDS = 30;
    private const BOT_SECONDS = 10;

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function start(array $state, ?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy()->timezone(config('app.timezone'));
        $seconds = $this->secondsFor($state);

        $state['turnTimer'] = [
            'secondsTotal' => $seconds,
            'startedAt' => $now->toIso8601String(),
            'expiresAt' => $now->copy()->addSeconds($seconds)->toIso8601String(),
            'serverNow' => $now->toIso8601String(),
            'isExpired' => false,
            'label' => 'Tempo da jogada',
        ];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function refresh(array $state, ?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy()->timezone(config('app.timezone'));

        if ((bool) ($state['isFinished'] ?? false)) {
            unset($state['turnTimer']);

            return $state;
        }

        if (! isset($state['turnTimer']['expiresAt'])) {
            return $this->start($state, $now);
        }

        $expiresAt = Carbon::parse((string) $state['turnTimer']['expiresAt'])
            ->timezone(config('app.timezone'));
        $secondsRemaining = max(0, $now->diffInSeconds($expiresAt, false));

        $state['turnTimer'] = [
            ...$state['turnTimer'],
            'serverNow' => $now->toIso8601String(),
            'secondsRemaining' => $secondsRemaining,
            'isExpired' => $secondsRemaining <= 0,
        ];

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function secondsFor(array $state): int
    {
        if ($this->currentTurnBelongsToMultiSeatBot($state)) {
            return self::BOT_SECONDS;
        }

        if ((bool) data_get($state, 'botVsBotSimulation', false)) {
            return self::BOT_SECONDS;
        }

        if ((bool) data_get($state, 'multiSeat.enabled', false)) {
            return self::DEFAULT_SECONDS;
        }

        $configured = (int) data_get($state, 'turnTimer.secondsTotal', self::DEFAULT_SECONDS);

        return max(10, min(120, $configured));
    }

    /**
     * @param array<string, mixed> $state
     */
    private function currentTurnBelongsToMultiSeatBot(array $state): bool
    {
        if (! (bool) data_get($state, 'multiSeat.enabled', false)) {
            return false;
        }

        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));

        if ($currentSeat <= 0) {
            return false;
        }

        $players = data_get($state, 'multiSeat.players', []);

        if (! is_array($players)) {
            return false;
        }

        foreach ($players as $player) {
            if (! is_array($player) || (int) ($player['seatNumber'] ?? 0) !== $currentSeat) {
                continue;
            }

            return (bool) ($player['isBot'] ?? $player['is_bot'] ?? false);
        }

        return false;
    }
}
