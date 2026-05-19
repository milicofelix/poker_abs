<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerTable;
use Illuminate\Support\Carbon;

final readonly class PokerTurnTimeoutService
{
    public function __construct(
        private LocalPokerPersistenceService $pokerPersistence,
        private PokerTableTurnActionService $turnAction,
        private PokerTurnTimerService $turnTimer,
        private PokerBotTurnProcessor $botTurnProcessor,
        private PokerMultiSeatTurnActionService $multiSeatTurnAction,
    ) {
    }

    /**
     * @return array{state: array<string, mixed>, processed: bool, action: string|null}
     */
    public function process(PokerTable $table, ?Carbon $now = null): array
    {
        $state = $this->pokerPersistence->currentStateForTable($table);

        abort_if(! $state, 404, 'Mesa sem mão ativa.');

        $state = $this->turnTimer->refresh($state, $now);

        if ($this->botTurnProcessor->isBotVsBotTable($table)) {
            $state['botVsBotSimulation'] = true;
        }

        $isMultiSeat = (bool) data_get($state, 'multiSeat.enabled', false);
        $currentSeatIsBot = $isMultiSeat && $this->multiSeatCurrentSeatIsBot($table, $state);

        if (! (bool) data_get($state, 'turnTimer.isExpired', false) && ! $currentSeatIsBot) {
            return [
                'state' => $state,
                'processed' => false,
                'action' => null,
            ];
        }

        if ($isMultiSeat) {
            $action = $currentSeatIsBot
                ? $this->botMultiSeatActionFor($table, $state)
                : $this->automaticMultiSeatActionFor($state);

            $nextState = $this->multiSeatTurnAction->executeAutomatic($state, $action);
            $nextState['turnTimeout'] = [
                'processed' => true,
                'action' => $currentSeatIsBot ? 'bot' : $action,
                'label' => $currentSeatIsBot ? 'Jogada automática do bot' : ($action === 'fold' ? 'Fold automático' : 'Check automático'),
                'message' => $currentSeatIsBot
                    ? 'Tempo do bot multi-seat processado automaticamente pela mesa.'
                    : ($action === 'fold'
                        ? 'Tempo esgotado: fold automático executado.'
                        : 'Tempo esgotado: check automático executado.'),
                'processedAt' => ($now ?? now())->copy()->timezone(config('app.timezone'))->toIso8601String(),
                'seatNumber' => (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0)),
                'engine' => 'multi_seat',
            ];

            if (isset($nextState['lastAction']) && is_array($nextState['lastAction'])) {
                $nextState['lastAction']['message'] = $nextState['turnTimeout']['message'];
                $nextState['lastAction']['isAutomatic'] = true;
                $nextState['lastAction']['isBot'] = $currentSeatIsBot;
            }

            $nextState = $this->pokerPersistence->persist($nextState);

            return [
                'state' => $nextState,
                'processed' => true,
                'action' => $currentSeatIsBot ? 'bot' : $action,
            ];
        }

        if ($this->botTurnProcessor->currentTurnBelongsToBot($table, $state)) {
            $nextState = $this->botTurnProcessor->processOne($table, $state);
            $nextState['botVsBotSimulation'] = $this->botTurnProcessor->isBotVsBotTable($table);
            $nextState['turnTimeout'] = [
                'processed' => true,
                'action' => 'bot',
                'label' => 'Jogada automática do bot',
                'message' => 'Tempo do bot processado automaticamente pela mesa.',
                'processedAt' => ($now ?? now())->copy()->timezone(config('app.timezone'))->toIso8601String(),
            ];

            $nextState = $this->pokerPersistence->persist($nextState);

            return [
                'state' => $nextState,
                'processed' => true,
                'action' => 'bot',
            ];
        }

        $action = $this->automaticActionFor($state);

        $nextState = $this->turnAction->executeAutomatic($state, $action);
        $nextState['turnTimeout'] = [
            'processed' => true,
            'action' => $action,
            'label' => $action === 'fold' ? 'Fold automático' : 'Check automático',
            'message' => $action === 'fold'
                ? 'Tempo esgotado: fold automático executado.'
                : 'Tempo esgotado: check automático executado.',
            'processedAt' => ($now ?? now())->copy()->timezone(config('app.timezone'))->toIso8601String(),
        ];

        if (isset($nextState['lastAction']) && is_array($nextState['lastAction'])) {
            $nextState['lastAction']['message'] = $nextState['turnTimeout']['message'];
            $nextState['lastAction']['isAutomatic'] = true;
        }

        $nextState = $this->pokerPersistence->persist($nextState);

        return [
            'state' => $nextState,
            'processed' => true,
            'action' => $action,
        ];
    }


    /**
     * @param array<string, mixed> $state
     */
    private function botMultiSeatActionFor(PokerTable $table, array $state): string
    {
        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));
        $players = data_get($state, 'multiSeat.players', []);

        if (! is_array($players)) {
            return 'check';
        }

        $currentPlayer = null;

        foreach ($players as $player) {
            if (is_array($player) && (int) ($player['seatNumber'] ?? 0) === $currentSeat) {
                $currentPlayer = $player;
                break;
            }
        }

        if (! is_array($currentPlayer)) {
            return 'check';
        }

        $amountToCall = max(0, (int) ($state['currentBet'] ?? 0) - (int) ($currentPlayer['streetBet'] ?? 0));
        $stack = (int) ($currentPlayer['stack'] ?? 0);
        $rank = (int) data_get($currentPlayer, 'bestHand.rank', 0);
        $profile = (string) ($table->realPlayers()
            ->where('seat_number', $currentSeat)
            ->where('is_bot', true)
            ->whereNull('left_at')
            ->value('bot_profile') ?: 'conservative');

        if ($amountToCall > 0) {
            if ($rank >= 4 || $amountToCall <= max(20, (int) floor($stack * 0.08)) || $profile === 'aggressive') {
                return 'call';
            }

            return 'fold';
        }

        return 'check';
    }

    /**
     * @param array<string, mixed> $state
     */
    private function automaticMultiSeatActionFor(array $state): string
    {
        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));
        $players = data_get($state, 'multiSeat.players', []);

        if (! is_array($players)) {
            return 'check';
        }

        foreach ($players as $player) {
            if (! is_array($player) || (int) ($player['seatNumber'] ?? 0) !== $currentSeat) {
                continue;
            }

            $amountToCall = max(0, (int) ($state['currentBet'] ?? 0) - (int) ($player['streetBet'] ?? 0));

            return $amountToCall > 0 ? 'fold' : 'check';
        }

        return 'check';
    }

    /**
     * @param array<string, mixed> $state
     */
    private function multiSeatCurrentSeatIsBot(PokerTable $table, array $state): bool
    {
        $currentSeat = (int) data_get($state, 'multiSeat.currentSeat', data_get($state, 'currentTurn.seatNumber', 0));

        if ($currentSeat <= 0) {
            return false;
        }

        return $table->realPlayers()
            ->where('seat_number', $currentSeat)
            ->where('is_bot', true)
            ->whereNull('left_at')
            ->exists();
    }

    /**
     * @param array<string, mixed> $state
     */
    private function automaticActionFor(array $state): string
    {
        return ((int) ($state['amountToCall'] ?? 0)) > 0 ? 'fold' : 'check';
    }
}
