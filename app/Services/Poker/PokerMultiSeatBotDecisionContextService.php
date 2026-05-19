<?php

namespace App\Services\Poker;

/**
 * Prepara uma leitura multi-oponente para bots em mesas 3+.
 *
 * A FASE 10.14 ainda não força o bot a executar uma ação sozinho; ela cria o
 * contexto estável que a camada de decisão/timeout pode consumir sem depender
 * do contrato heads-up antigo.
 */
final class PokerMultiSeatBotDecisionContextService
{
    /**
     * @param array<string, mixed> $state
     * @return array<int, array<string, mixed>>
     */
    public function forState(array $state): array
    {
        if (! (bool) data_get($state, 'multiSeat.enabled', false)) {
            return [];
        }

        $players = $this->players($state);
        $activePlayers = $this->activePlayers($players);
        $currentBet = (int) ($state['currentBet'] ?? 0);
        $pot = (int) ($state['pot'] ?? 0);
        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));
        $contexts = [];

        foreach ($players as $player) {
            if (! $this->isBot($player)) {
                continue;
            }

            $seatNumber = (int) ($player['seatNumber'] ?? 0);

            if ($seatNumber <= 0 || (bool) ($player['hasFolded'] ?? false)) {
                continue;
            }

            $streetBet = (int) ($player['streetBet'] ?? 0);
            $stack = (int) ($player['stack'] ?? 0);
            $amountToCall = max(0, $currentBet - $streetBet);
            $activeOpponents = array_values(array_filter(
                $activePlayers,
                static fn (array $opponent): bool => (int) ($opponent['seatNumber'] ?? 0) !== $seatNumber,
            ));

            $contexts[] = [
                'phase' => '10.14',
                'seatNumber' => $seatNumber,
                'tablePlayerId' => (int) ($player['tablePlayerId'] ?? 0),
                'nickname' => (string) ($player['nickname'] ?? 'Bot'),
                'profile' => (string) ($player['botProfile'] ?? $player['bot_profile'] ?? 'conservative'),
                'difficulty' => (string) ($player['botDifficulty'] ?? $player['bot_difficulty'] ?? 'normal'),
                'isCurrentTurn' => $seatNumber === $currentSeat,
                'activeOpponentCount' => count($activeOpponents),
                'activeOpponentSeats' => array_values(array_map(
                    static fn (array $opponent): int => (int) ($opponent['seatNumber'] ?? 0),
                    $activeOpponents,
                )),
                'amountToCall' => $amountToCall,
                'currentBet' => $currentBet,
                'streetBet' => $streetBet,
                'stack' => $stack,
                'pot' => $pot,
                'potOdds' => $this->potOdds($amountToCall, $pot),
                'stackPressure' => $this->stackPressure($stack, $pot, $amountToCall),
                'canCheck' => $amountToCall === 0,
                'canCall' => $amountToCall > 0 && $stack > 0,
                'canRaise' => $stack > $amountToCall,
            ];
        }

        usort($contexts, static function (array $left, array $right): int {
            return (int) ($left['seatNumber'] ?? 0) <=> (int) ($right['seatNumber'] ?? 0);
        });

        return $contexts;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<int, array<string, mixed>>
     */
    private function players(array $state): array
    {
        $players = data_get($state, 'multiSeat.players', []);

        return is_array($players)
            ? array_values(array_filter($players, static fn (mixed $player): bool => is_array($player)))
            : [];
    }

    /**
     * @param array<int, array<string, mixed>> $players
     * @return array<int, array<string, mixed>>
     */
    private function activePlayers(array $players): array
    {
        return array_values(array_filter(
            $players,
            static fn (array $player): bool => ! (bool) ($player['hasFolded'] ?? false) && (int) ($player['seatNumber'] ?? 0) > 0,
        ));
    }

    /**
     * @param array<string, mixed> $player
     */
    private function isBot(array $player): bool
    {
        return (bool) ($player['isBot'] ?? $player['is_bot'] ?? false)
            || (string) ($player['role'] ?? '') === 'bot'
            || (string) ($player['role'] ?? '') === 'simple_bot'
            || isset($player['botProfile'])
            || isset($player['bot_profile']);
    }

    private function potOdds(int $amountToCall, int $pot): int
    {
        if ($amountToCall <= 0) {
            return 0;
        }

        return (int) round(($amountToCall / max(1, $pot + $amountToCall)) * 100);
    }

    private function stackPressure(int $stack, int $pot, int $amountToCall): string
    {
        if ($stack <= 0) {
            return 'all_in';
        }

        if ($amountToCall > 0 && $stack <= $amountToCall) {
            return 'forced_all_in';
        }

        $spr = $pot > 0 ? $stack / max(1, $pot) : $stack;

        if ($spr <= 1.5) {
            return 'short';
        }

        if ($spr <= 4) {
            return 'medium';
        }

        return 'comfortable';
    }
}
