<?php

namespace App\Events\Poker;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
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
            'state' => $this->state,
        ];
    }
}
