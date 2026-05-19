<?php

namespace App\Data\Poker;

use App\Models\Poker\PokerTable;

final readonly class MultiSeatPokerTableStateContract
{
    /**
     * @param array<int, int> $supportedSeats
     * @param array<int, array<string, mixed>> $turnOrder
     * @param array<string, mixed> $blinds
     */
    public function __construct(
        public string $schemaVersion,
        public string $engineMode,
        public bool $isCurrentEngine,
        public bool $isEnabled,
        public int $declaredMaxPlayers,
        public int $currentEngineMaxPlayers,
        public array $supportedSeats,
        public array $turnOrder,
        public array $blinds,
        public string $note,
        public array $dealPreview,
        public array $turnCyclePreview,
        public array $actionPreview,
        public array $bettingRoundPreview,
        public array $showdownPreview,
    ) {
    }

    /**
     * @param array<string, mixed> $preparationPayload
     */
    public static function fromPreparationPayload(PokerTable $table, array $preparationPayload): self
    {
        $declaredMaxPlayers = $table->declaredMaxPlayers();

        return new self(
            schemaVersion: 'multi_seat.v0',
            engineMode: 'multi_seat_preparation',
            isCurrentEngine: false,
            isEnabled: false,
            declaredMaxPlayers: $declaredMaxPlayers,
            currentEngineMaxPlayers: $table->currentEngineMaxPlayers(),
            supportedSeats: range(1, $declaredMaxPlayers),
            turnOrder: array_values(array_filter(
                $preparationPayload['turnOrder'] ?? [],
                static fn (mixed $item): bool => is_array($item),
            )),
            blinds: is_array($preparationPayload['blinds'] ?? null) ? $preparationPayload['blinds'] : [],
            note: 'Contrato futuro da mesa multi-seat. Ele documenta assentos, ordem e blinds, mas ainda não executa jogadas 3+.',
            dealPreview: is_array($preparationPayload['dealPreview'] ?? null) ? $preparationPayload['dealPreview'] : [],
            turnCyclePreview: is_array($preparationPayload['turnCyclePreview'] ?? null) ? $preparationPayload['turnCyclePreview'] : [],
            actionPreview: is_array($preparationPayload['actionPreview'] ?? null) ? $preparationPayload['actionPreview'] : [],
            bettingRoundPreview: is_array($preparationPayload['bettingRoundPreview'] ?? null) ? $preparationPayload['bettingRoundPreview'] : [],
            showdownPreview: is_array($preparationPayload['showdownPreview'] ?? null) ? $preparationPayload['showdownPreview'] : [],
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
            'isEnabled' => $this->isEnabled,
            'declaredMaxPlayers' => $this->declaredMaxPlayers,
            'currentEngineMaxPlayers' => $this->currentEngineMaxPlayers,
            'supportedSeats' => $this->supportedSeats,
            'turnOrder' => $this->turnOrder,
            'blinds' => $this->blinds,
            'note' => $this->note,
            'dealPreview' => $this->dealPreview,
            'turnCyclePreview' => $this->turnCyclePreview,
            'actionPreview' => $this->actionPreview,
            'bettingRoundPreview' => $this->bettingRoundPreview,
            'showdownPreview' => $this->showdownPreview,
        ];
    }
}
