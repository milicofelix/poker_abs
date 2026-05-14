<?php

namespace App\Services\Poker;

use App\Application\Poker\PlayLocalPokerRoundAction;
use App\Models\Poker\PokerTable;
use Illuminate\Support\Carbon;

final readonly class PokerTurnTimeoutService
{
    public function __construct(
        private LocalPokerPersistenceService $pokerPersistence,
        private PlayLocalPokerRoundAction $playRound,
        private PokerTurnTimerService $turnTimer,
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

        if (! (bool) data_get($state, 'turnTimer.isExpired', false)) {
            return [
                'state' => $state,
                'processed' => false,
                'action' => null,
            ];
        }

        $action = $this->automaticActionFor($state);

        $nextState = $this->playRound->execute($state, $action);
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
