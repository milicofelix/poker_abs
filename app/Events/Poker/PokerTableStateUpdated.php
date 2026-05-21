<?php

namespace App\Events\Poker;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use App\Services\Poker\PokerMultiSeatRealtimeSyncContractService;
use Illuminate\Queue\SerializesModels;

final class PokerTableStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string, mixed> $state
     */
    public function __construct(
        public readonly int $tableId,
        public readonly array $state,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('poker.tables.'.$this->tableId);
    }

    public function broadcastAs(): string
    {
        return 'poker.table.state.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tableId' => $this->tableId,
            'state' => $this->compactStateForBroadcast($this->state),
        ];
    }

    /**
     * Mantém o evento realtime leve para evitar o erro
     * "Pusher error: Payload too large" quando a mão acumula
     * histórico, contexto de bot, memória e estatísticas.
     *
     * A tela já usa este evento como gatilho para reidratar o estado
     * completo via endpoint da mesa, então o broadcast não precisa carregar
     * todo o payload persistido da mão.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function compactStateForBroadcast(array $state): array
    {
        return array_filter([
            'persistence' => array_filter([
                'tableId' => data_get($state, 'persistence.tableId'),
                'syncVersion' => data_get($state, 'persistence.syncVersion'),
            ], static fn (mixed $value): bool => $value !== null),
            'street' => data_get($state, 'street'),
            'streetLabel' => data_get($state, 'streetLabel'),
            'isFinished' => data_get($state, 'isFinished'),
            'lastAction' => $this->compactLastAction($state['lastAction'] ?? null),
            'multiSeatRealtime' => $this->compactMultiSeatRealtime($state),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }


    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>|null
     */
    private function compactMultiSeatRealtime(array $state): ?array
    {
        if (! (bool) data_get($state, 'multiSeat.enabled', false)) {
            return null;
        }

        return (new PokerMultiSeatRealtimeSyncContractService())->forState($state);
    }

    /**
     * @param mixed $lastAction
     * @return array<string, mixed>|null
     */
    private function compactLastAction(mixed $lastAction): ?array
    {
        if (! is_array($lastAction)) {
            return null;
        }

        $message = isset($lastAction['message']) ? (string) $lastAction['message'] : null;

        if ($message !== null && mb_strlen($message) > 180) {
            $message = mb_substr($message, 0, 177).'...';
        }

        return array_filter([
            'actor' => $lastAction['actor'] ?? null,
            'actorLabel' => $lastAction['actorLabel'] ?? null,
            'action' => $lastAction['action'] ?? null,
            'amount' => $lastAction['amount'] ?? null,
            'message' => $message,
            'isBot' => $lastAction['isBot'] ?? null,
            'isAutomatic' => $lastAction['isAutomatic'] ?? null,
            'botProfile' => $lastAction['botProfile'] ?? null,
            'botDifficulty' => $lastAction['botDifficulty'] ?? null,
            'botPersonality' => $lastAction['botPersonality'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
