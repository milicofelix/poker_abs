<?php

namespace App\Application\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Deck;
use App\Domain\Poker\Hands\HandEvaluator;

final readonly class StartPokerHandAction
{
    public function __construct(
        private HandEvaluator $handEvaluator,
    ) {
    }

    /**
     * @return array{
     *     playerCards: array<int, array{rank: string, suit: string, label: string}>,
     *     opponentCards: array<int, array{rank: string, suit: string, label: string}>,
     *     communityCards: array<int, array{rank: string, suit: string, label: string}>,
     *     bestHand: array{name: string, rank: int, kickers: array<int, int>, cards: array<int, array{rank: string, suit: string, label: string}>},
     *     opponentBestHand: array{name: string, rank: int, kickers: array<int, int>, cards: array<int, array{rank: string, suit: string, label: string}>}
     * }
     */
    public function execute(): array
    {
        $deck = Deck::standard()->shuffle();

        $playerCards = $deck->draw(2);
        $opponentCards = $deck->draw(2);
        $communityCards = $deck->draw(5);

        $bestHand = $this->handEvaluator->evaluate([
            ...$playerCards,
            ...$communityCards,
        ]);

        $opponentBestHand = $this->handEvaluator->evaluate([
            ...$opponentCards,
            ...$communityCards,
        ]);

        $smallBlind = 10;
        $bigBlind = 20;

        return [
            'street' => 'pre_flop',
            'streetLabel' => 'Pré-flop',
            'pot' => $smallBlind + $bigBlind,
            'playerStack' => 1000 - $smallBlind,
            'opponentStack' => 1000 - $bigBlind,
            'currentBet' => $bigBlind,
            'playerStreetBet' => $smallBlind,
            'opponentStreetBet' => $bigBlind,
            'amountToCall' => $bigBlind - $smallBlind,
            'minimumRaise' => $bigBlind,
            'minimumRaiseTo' => $bigBlind * 2,
            'maximumRaiseTo' => 1000,
            'smallBlind' => $smallBlind,
            'bigBlind' => $bigBlind,
            'dealerPosition' => 1,
            'canCheck' => false,
            'canCall' => true,
            'canRaise' => true,
            'canAct' => true,
            'bettingSummary' => [
                'playerCommitted' => $smallBlind,
                'opponentCommitted' => $bigBlind,
                'amountToCall' => $bigBlind - $smallBlind,
                'currentBet' => $bigBlind,
            ],
            'lastAction' => null,
            'actionHistory' => [],
            'conclusion' => null,
            'isFinished' => false,
            'playerCards' => array_map($this->serializeCard(...), $playerCards),
            'opponentCards' => array_map($this->serializeCard(...), $opponentCards),
            'communityCards' => array_map($this->serializeCard(...), $communityCards),
            'bestHand' => [
                'name' => $bestHand->rank->label(),
                'rank' => $bestHand->rank->value,
                'kickers' => $bestHand->kickers,
                'cards' => array_map($this->serializeCard(...), $bestHand->cards()),
            ],
            'opponentBestHand' => [
                'name' => $opponentBestHand->rank->label(),
                'rank' => $opponentBestHand->rank->value,
                'kickers' => $opponentBestHand->kickers,
                'cards' => array_map($this->serializeCard(...), $opponentBestHand->cards()),
            ],
        ];
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
