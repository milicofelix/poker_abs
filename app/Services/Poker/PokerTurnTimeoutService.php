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

        if (! (bool) data_get($state, 'turnTimer.isExpired', false)) {
            return [
                'state' => $state,
                'processed' => false,
                'action' => null,
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
    private function automaticActionFor(array $state): string
    {
        return ((int) ($state['amountToCall'] ?? 0)) > 0 ? 'fold' : 'check';
    }
}
