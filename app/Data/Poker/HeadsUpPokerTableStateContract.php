<?php

namespace App\Data\Poker;

use App\Models\Poker\PokerTable;

final readonly class HeadsUpPokerTableStateContract
{
    /**
     * @param array<int, string> $canonicalActors
     * @param array<int, int> $supportedSeats
     * @param array<string, mixed> $state
     */
    public function __construct(
        public string $schemaVersion,
        public string $engineMode,
        public bool $isCurrentEngine,
        public array $canonicalActors,
        public array $supportedSeats,
        public array $state,
        public string $note,
    ) {
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function fromTableState(PokerTable $table, array $state): self
    {
        return new self(
            schemaVersion: 'heads_up.v1',
            engineMode: 'heads_up',
            isCurrentEngine: true,
            canonicalActors: ['player', 'opponent'],
            supportedSeats: range(1, $table->currentEngineMaxPlayers()),
            state: [
                'street' => $state['street'] ?? null,
                'pot' => (int) ($state['pot'] ?? 0),
                'currentTurn' => is_array($state['currentTurn'] ?? null) ? $state['currentTurn'] : null,
                'isFinished' => (bool) ($state['isFinished'] ?? false),
                'isWaitingForPlayers' => (bool) ($state['isWaitingForPlayers'] ?? false),
                'turnTimer' => is_array($state['turnTimer'] ?? null) ? $state['turnTimer'] : null,
            ],
            note: 'Contrato atual da mesa heads-up. Continua sendo a fonte oficial do jogo até a engine multi-seat ser ativada.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => $this->schemaVersion,
            'engineMode' => $this->engineMode,
            'isCurrentEngine' => $this->isCurrentEngine,
            'canonicalActors' => $this->canonicalActors,
            'supportedSeats' => $this->supportedSeats,
            'state' => $this->state,
            'note' => $this->note,
        ];
    }
}
