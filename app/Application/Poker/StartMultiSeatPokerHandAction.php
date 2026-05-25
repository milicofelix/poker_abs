<?php

namespace App\Application\Poker;

use App\Domain\Poker\Cards\Card;
use App\Domain\Poker\Cards\Deck;
use App\Domain\Poker\Hands\HandEvaluator;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\Poker\PokerTournament;
use App\Services\Poker\PokerMultiSeatBlindRotationService;

final readonly class StartMultiSeatPokerHandAction
{
    public function __construct(
        private HandEvaluator $handEvaluator,
        private PokerMultiSeatBlindRotationService $blindRotation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(PokerTable $table): array
    {
        $players = $this->playablePlayersForNewHand($table);
        $table->unsetRelation('realPlayers');
        $blindPositions = $this->blindRotation->positionsForNewHand($table);

        $deck = Deck::standard()->shuffle();
        $communityCards = $deck->draw(5);
        $smallBlind = (int) $table->small_blind;
        $bigBlind = (int) $table->big_blind;
        $dealerSeat = (int) ($blindPositions['dealerSeat'] ?? 1);
        $smallBlindSeat = (int) ($blindPositions['smallBlindSeat'] ?? $dealerSeat);
        $bigBlindSeat = (int) ($blindPositions['bigBlindSeat'] ?? $dealerSeat);
        $firstPreFlopSeat = (int) ($blindPositions['firstPreFlopSeat'] ?? $dealerSeat);
        $firstPostFlopSeat = (int) ($blindPositions['firstPostFlopSeat'] ?? $dealerSeat);

        $multiSeatPlayers = $players->map(function (PokerTablePlayer $player) use ($deck, $communityCards, $dealerSeat, $smallBlindSeat, $bigBlindSeat, $smallBlind, $bigBlind): array {
            $cards = $deck->draw(2);
            $streetBet = match ((int) $player->seat_number) {
                $smallBlindSeat => min($smallBlind, max(0, (int) $player->stack)),
                $bigBlindSeat => min($bigBlind, max(0, (int) $player->stack)),
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
                'status' => $stack === 0 && $streetBet > 0 ? 'all_in' : 'active',
                'stack' => $stack,
                'streetBet' => $streetBet,
                'hasFolded' => false,
                'hasActed' => false,
                'isDealer' => (int) $player->seat_number === $dealerSeat,
                'isSmallBlind' => (int) $player->seat_number === $smallBlindSeat,
                'isBigBlind' => (int) $player->seat_number === $bigBlindSeat,
                'cards' => array_map($this->serializeCard(...), $cards),
                'bestHand' => [
                    'name' => $bestHand->rank->label(),
                    'rank' => $bestHand->rank->value,
                    'kickers' => $bestHand->kickers,
                    'cards' => array_map($this->serializeCard(...), $bestHand->cards()),
                'highlightCards' => array_map($this->serializeCard(...), $bestHand->highlightCards()),
                ],
            ];
        })->all();

        $firstPlayer = $multiSeatPlayers[0] ?? null;
        $secondPlayer = $multiSeatPlayers[1] ?? null;
        $amountToCall = max(0, $bigBlind - (int) ($firstPlayer['streetBet'] ?? 0));

        return [
            'street' => 'pre_flop',
            'streetLabel' => 'Pré-flop',
            'pot' => array_sum(array_map(static fn (array $player): int => (int) ($player['streetBet'] ?? 0), $multiSeatPlayers)),
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
                'phase' => '12.7',
                'mode' => 'controlled_activation',
                'dealerSeat' => $dealerSeat,
                'smallBlindSeat' => $smallBlindSeat,
                'bigBlindSeat' => $bigBlindSeat,
                'firstPreFlopSeat' => $firstPreFlopSeat,
                'firstPostFlopSeat' => $firstPostFlopSeat,
                'currentSeat' => $firstPreFlopSeat,
                'blinds' => [
                    'phase' => '12.7',
                    'dealerSeat' => $dealerSeat,
                    'smallBlindSeat' => $smallBlindSeat,
                    'bigBlindSeat' => $bigBlindSeat,
                    'firstPreFlopSeat' => $firstPreFlopSeat,
                    'firstPostFlopSeat' => $firstPostFlopSeat,
                    'smallBlindAmount' => $smallBlind,
                    'bigBlindAmount' => $bigBlind,
                    'order' => $blindPositions['order'] ?? [],
                ],
                'players' => $multiSeatPlayers,
                'message' => 'Mão multi-seat iniciada com dealer button e blinds automáticos.',
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
     * Garante que a nova mão multi-seat só considere assentos realmente jogáveis.
     *
     * Jogadores/bots sem stack permanecem sentados, mas ficam fora da próxima mão
     * até existir um rebuy manual. Isso evita loop infinito de rebuy automático.
     *
     * @return \Illuminate\Support\Collection<int, PokerTablePlayer>
     */
    private function playablePlayersForNewHand(PokerTable $table)
    {
        return $this->blindRotation->seatedPlayers($table)
            ->filter(static fn (PokerTablePlayer $player): bool => (int) $player->stack > 0)
            ->values();
    }


    private function isTournamentRuntimeTable(PokerTable $table): bool
    {
        return PokerTournament::query()
            ->where('poker_table_id', $table->id)
            ->whereIn('status', [PokerTournament::STATUS_RUNNING, PokerTournament::STATUS_FINISHED])
            ->exists();
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
