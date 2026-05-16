<?php

namespace App\Services\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use Illuminate\Support\Collection;

final class PokerTableReadinessService
{
    /**
     * @return Collection<int, PokerTablePlayer>
     */
    public function seatedPlayers(PokerTable $table): Collection
    {
        return $table->realPlayers()
            ->whereNotNull('seat_number')
            ->whereNull('left_at')
            ->where(function ($query): void {
                $query->where('status', 'online')
                    ->orWhere('is_bot', true);
            })
            ->orderBy('seat_number')
            ->orderBy('id')
            ->get();
    }

    public function canStartHand(PokerTable $table): bool
    {
        return $this->seatedPlayers($table)->count() >= min(2, (int) $table->max_players);
    }

    /**
     * @return array<string, mixed>
     */
    public function startIfReady(
        PokerTable $table,
        StartPokerHandAction $startPokerHand,
        LocalPokerPersistenceService $pokerPersistence,
    ): array {
        $runningState = $pokerPersistence->currentStateForTable($table);

        if ($runningState) {
            return $runningState;
        }

        if (! $this->canStartHand($table)) {
            return $this->waitingState($table);
        }

        return $pokerPersistence->startOnTable($table, $startPokerHand->execute());
    }

    /**
     * @return array<string, mixed>
     */
    public function waitingState(PokerTable $table): array
    {
        $seatedPlayers = $this->seatedPlayers($table);
        $playersSeated = $seatedPlayers->count();
        $playersNeeded = max(0, min(2, (int) $table->max_players) - $playersSeated);

        return [
            'street' => 'waiting',
            'streetLabel' => 'Aguardando',
            'pot' => 0,
            'playerStack' => (int) ($seatedPlayers->get(0)?->stack ?? 1000),
            'opponentStack' => (int) ($seatedPlayers->get(1)?->stack ?? 1000),
            'currentBet' => 0,
            'playerStreetBet' => 0,
            'opponentStreetBet' => 0,
            'amountToCall' => 0,
            'minimumRaise' => (int) $table->big_blind,
            'minimumRaiseTo' => (int) $table->big_blind,
            'maximumRaiseTo' => 0,
            'smallBlind' => (int) $table->small_blind,
            'bigBlind' => (int) $table->big_blind,
            'dealerPosition' => null,
            'canCheck' => false,
            'canCall' => false,
            'canRaise' => false,
            'canAct' => false,
            'bettingSummary' => [
                'playerCommitted' => 0,
                'opponentCommitted' => 0,
                'amountToCall' => 0,
                'currentBet' => 0,
            ],
            'tableSeats' => $this->waitingSeats($table),
            'lastAction' => null,
            'actionHistory' => [],
            'conclusion' => null,
            'turnTimer' => null,
            'isFinished' => false,
            'isWaitingForPlayers' => true,
            'waitingForPlayers' => [
                'playersSeated' => $playersSeated,
                'playersNeeded' => $playersNeeded,
                'minimumPlayers' => min(2, (int) $table->max_players),
                'message' => $playersNeeded > 0
                    ? 'A mesa precisa de mais jogador sentado para iniciar a mão.'
                    : 'Mesa pronta para iniciar a mão.',
            ],
            'currentTurn' => [
                'actor' => 'waiting',
                'canonicalActor' => 'waiting',
                'actorLabel' => 'Aguardando jogadores',
                'isCurrentUserTurn' => false,
                'message' => 'Entre, sente em um assento e adicione um bot ou outro jogador para iniciar.',
            ],
            'playerCards' => [],
            'opponentCards' => [],
            'communityCards' => [],
            'bestHand' => null,
            'opponentBestHand' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function waitingSeats(PokerTable $table): array
    {
        $playersBySeat = $this->seatedPlayers($table)->keyBy('seat_number');
        $seats = [];

        for ($seatNumber = 1; $seatNumber <= (int) $table->max_players; $seatNumber++) {
            $player = $playersBySeat->get($seatNumber);

            $seats[] = [
                'seatNumber' => $seatNumber,
                'status' => $player ? 'occupied' : 'available',
                'role' => $player?->is_bot ? 'simple_bot' : 'real_player',
                'stackSnapshot' => (int) ($player?->stack ?? 1000),
                'isDealer' => $seatNumber === 1 && $player !== null,
                'isSmallBlind' => $seatNumber === 1 && $player !== null,
                'isBigBlind' => $seatNumber === 2 && $player !== null,
            ];
        }

        return $seats;
    }
}
