<?php

namespace App\Application\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Deck;
use App\Domain\Poker\Hands\HandEvaluator;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;

final readonly class StartMultiSeatPokerHandAction
{
    public function __construct(
        private HandEvaluator $handEvaluator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(PokerTable $table): array
    {
        $players = $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->where(function ($query): void {
                $query->where('status', 'online')
                    ->orWhere('is_bot', true);
            })
            ->orderBy('seat_number')
            ->orderBy('id')
            ->get()
            ->values();

        $deck = Deck::standard()->shuffle();
        $communityCards = $deck->draw(5);
        $smallBlind = (int) $table->small_blind;
        $bigBlind = (int) $table->big_blind;
        $dealerSeat = 1;
        $smallBlindSeat = 2;
        $bigBlindSeat = 3;
        $firstPreFlopSeat = 1;

        $multiSeatPlayers = $players->map(function (PokerTablePlayer $player) use ($deck, $communityCards, $smallBlindSeat, $bigBlindSeat, $smallBlind, $bigBlind): array {
            $cards = $deck->draw(2);
            $streetBet = match ((int) $player->seat_number) {
                $smallBlindSeat => $smallBlind,
                $bigBlindSeat => $bigBlind,
                default => 0,
            };
            $stack = max(0, (int) $player->stack - $streetBet);
            $bestHand = $this->handEvaluator->evaluate([
                ...$cards,
                ...$communityCards,
            ]);

            return [
                'tablePlayerId' => $player->id,
                'userId' => $player->user_id,
                'nickname' => $player->nickname ?: 'Jogador '.$player->seat_number,
                'displayName' => $player->nickname ?: 'Jogador '.$player->seat_number,
                'seatNumber' => (int) $player->seat_number,
                'isBot' => (bool) $player->is_bot,
                'status' => 'active',
                'stack' => $stack,
                'streetBet' => $streetBet,
                'hasFolded' => false,
                'hasActed' => false,
                'cards' => array_map($this->serializeCard(...), $cards),
                'bestHand' => [
                    'name' => $bestHand->rank->label(),
                    'rank' => $bestHand->rank->value,
                    'kickers' => $bestHand->kickers,
                    'cards' => array_map($this->serializeCard(...), $bestHand->cards()),
                ],
            ];
        })->all();

        $firstPlayer = $multiSeatPlayers[0] ?? null;
        $secondPlayer = $multiSeatPlayers[1] ?? null;
        $amountToCall = max(0, $bigBlind - (int) ($firstPlayer['streetBet'] ?? 0));

        return [
            'street' => 'pre_flop',
            'streetLabel' => 'Pré-flop',
            'pot' => $smallBlind + $bigBlind,
            'playerStack' => (int) ($firstPlayer['stack'] ?? 1000),
            'opponentStack' => (int) ($secondPlayer['stack'] ?? 1000),
            'currentBet' => $bigBlind,
            'playerStreetBet' => (int) ($firstPlayer['streetBet'] ?? 0),
            'opponentStreetBet' => (int) ($secondPlayer['streetBet'] ?? 0),
            'amountToCall' => $amountToCall,
            'minimumRaise' => $bigBlind,
            'minimumRaiseTo' => $bigBlind * 2,
            'maximumRaiseTo' => (int) ($firstPlayer['stack'] ?? 1000) + (int) ($firstPlayer['streetBet'] ?? 0),
            'smallBlind' => $smallBlind,
            'bigBlind' => $bigBlind,
            'dealerPosition' => $dealerSeat,
            'canCheck' => $amountToCall === 0,
            'canCall' => $amountToCall > 0,
            'canRaise' => true,
            'canAct' => true,
            'bettingSummary' => [
                'amountToCall' => $amountToCall,
                'currentBet' => $bigBlind,
                'currentSeat' => $firstPreFlopSeat,
            ],
            'lastAction' => null,
            'actionHistory' => [],
            'conclusion' => null,
            'isFinished' => false,
            'isWaitingForPlayers' => false,
            'playerCards' => (array) ($firstPlayer['cards'] ?? []),
            'opponentCards' => (array) ($secondPlayer['cards'] ?? []),
            'communityCards' => array_map($this->serializeCard(...), $communityCards),
            'bestHand' => $firstPlayer['bestHand'] ?? null,
            'opponentBestHand' => $secondPlayer['bestHand'] ?? null,
            'multiSeat' => [
                'enabled' => true,
                'phase' => '10.9',
                'mode' => 'controlled_activation',
                'dealerSeat' => $dealerSeat,
                'smallBlindSeat' => $smallBlindSeat,
                'bigBlindSeat' => $bigBlindSeat,
                'currentSeat' => $firstPreFlopSeat,
                'players' => $multiSeatPlayers,
                'message' => 'Mão multi-seat iniciada em ativação controlada.',
            ],
            'currentTurn' => [
                'actor' => 'seat:'.$firstPreFlopSeat,
                'seatNumber' => $firstPreFlopSeat,
                'actedThisStreet' => [],
                'label' => 'Vez do assento '.$firstPreFlopSeat,
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
