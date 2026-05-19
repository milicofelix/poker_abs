<?php

namespace App\Services\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Deck;
use App\Models\Poker\PokerTable;

final class PokerMultiSeatDealPreviewService
{
    public function __construct(
        private readonly PokerMultiSeatEnginePreparationService $preparation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(PokerTable $table): array
    {
        $turnOrder = $this->seatedTurnOrder($table);
        $deck = Deck::standard()->shuffle();
        $seatHands = [];

        foreach ($turnOrder as $player) {
            $seatHands[] = [
                'tablePlayerId' => $player['tablePlayerId'],
                'userId' => $player['userId'],
                'nickname' => $player['nickname'],
                'seatNumber' => $player['seatNumber'],
                'isBot' => $player['isBot'],
                'cards' => array_map($this->serializeCard(...), $deck->draw(2)),
                'cardsCount' => 2,
                'isPrivatePayloadReady' => true,
            ];
        }

        return [
            'phase' => '10.3',
            'schemaVersion' => 'multi_seat_deal.v0',
            'enabledInMainEngine' => false,
            'declaredMaxPlayers' => $table->declaredMaxPlayers(),
            'activeSeatedPlayers' => count($turnOrder),
            'seatHands' => $seatHands,
            'communityCards' => array_map($this->serializeCard(...), $deck->draw(5)),
            'communityCardsCount' => 5,
            'remainingDeckCards' => $deck->remaining(),
            'privatePayloadPolicy' => 'Cada jogador deve receber somente as cartas do proprio assento quando o motor 3+ for ativado.',
            'note' => 'Preview tecnico da distribuicao multi-seat; ainda nao substitui o motor heads-up da mesa.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seatedTurnOrder(PokerTable $table): array
    {
        $seatedPlayerIds = $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        if ($seatedPlayerIds === []) {
            return [];
        }

        return array_values(array_filter(
            $this->preparation->turnOrder($table),
            static fn (array $player): bool => in_array((int) $player['tablePlayerId'], $seatedPlayerIds, true),
        ));
    }

    /**
     * @return array{rank: string, suit: string, label: string}
     */
    private function serializeCard(Card $card): array
    {
        return [
            'rank' => $card->rank->value,
            'suit' => $card->suit->value,
            'label' => $card->label(),
        ];
    }
}
