<?php

namespace App\Services\Poker;

use App\Application\Poker\StartMultiSeatPokerHandAction;
use App\Application\Poker\StartPokerHandAction;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\Poker\PokerTournament;
use Illuminate\Support\Collection;

final class PokerTableReadinessService
{
    public function __construct(
        private readonly StartMultiSeatPokerHandAction $startMultiSeatPokerHand,
    ) {
    }

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

    /**
     * @return Collection<int, PokerTablePlayer>
     */
    public function playableSeatedPlayers(PokerTable $table): Collection
    {
        $players = $this->seatedPlayers($table);

        if (! $this->isTournamentRuntimeTable($table)) {
            return $players;
        }

        return $players
            ->filter(static fn (PokerTablePlayer $player): bool => (int) $player->stack > 0)
            ->values();
    }

    public function canStartHand(PokerTable $table): bool
    {
        if ($this->isTournamentRuntimeTable($table)) {
            return $this->playableSeatedPlayers($table)->count() >= $this->minimumPlayersToStart($table);
        }

        $playersSeated = $this->seatedPlayers($table)->count();

        if ($table->isMultiSeatCandidate()) {
            return $playersSeated >= PokerMultiSeatEngineActivationService::MINIMUM_PLAYERS;
        }

        return $playersSeated >= $this->minimumPlayersToStart($table);
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

        if ($table->isMultiSeatCandidate()) {
            return $pokerPersistence->startMultiSeatOnTable($table, $this->startMultiSeatPokerHand->execute($table));
        }

        return $pokerPersistence->startOnTable($table, $startPokerHand->execute());
    }


    /**
     * Inicia uma nova mão apenas quando o jogador aciona explicitamente o botão.
     *
     * Isso evita que refresh, polling de estado ou escolha de assento criem uma
     * mão sozinhos e bloqueiem a entrada dos jogadores restantes em mesas 3+.
     *
     * @return array<string, mixed>
     */
    public function startExplicitNewHand(
        PokerTable $table,
        LocalPokerPersistenceService $pokerPersistence,
        bool $isBotVsBotSimulation = false,
    ): array {
        if ($table->isMultiSeatCandidate() || $this->isTournamentRuntimeTable($table)) {
            return $pokerPersistence->startMultiSeatOnTable(
                $table,
                $this->startMultiSeatPokerHand->execute($table),
            );
        }

        $state = app(StartPokerHandAction::class)->execute();
        $state['botVsBotSimulation'] = $isBotVsBotSimulation;

        return $pokerPersistence->startOnTable($table, $state);
    }

    /**
     * @return array<string, mixed>
     */
    public function waitingState(PokerTable $table): array
    {
        $seatedPlayers = $this->isTournamentRuntimeTable($table)
            ? $this->playableSeatedPlayers($table)
            : $this->seatedPlayers($table);
        $playersSeated = $seatedPlayers->count();
        $minimumPlayers = $this->minimumPlayersToStart($table);
        $playersNeeded = max(0, $minimumPlayers - $playersSeated);

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
            'tableCapacity' => $table->capacityPayload(),
            'waitingForPlayers' => [
                'playersSeated' => $playersSeated,
                'playersNeeded' => $playersNeeded,
                'minimumPlayers' => $minimumPlayers,
                'message' => $playersNeeded > 0
                    ? 'A mesa precisa de mais jogador sentado para iniciar a mão.'
                    : 'Mesa pronta para iniciar a mão.',
            ],
            'multiSeat' => $this->isTournamentRuntimeTable($table) ? [
                'enabled' => true,
                'phase' => '13.2.4.2',
                'mode' => 'tournament_waiting_snapshot',
                'players' => $this->waitingMultiSeatPlayers($seatedPlayers),
                'message' => 'Snapshot seguro da mesa de torneio sem jogadores eliminados.',
            ] : null,
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
     * @param Collection<int, PokerTablePlayer> $players
     * @return array<int, array<string, mixed>>
     */
    private function waitingMultiSeatPlayers(Collection $players): array
    {
        return $players
            ->map(static fn (PokerTablePlayer $player): array => [
                'tablePlayerId' => $player->id,
                'userId' => $player->user_id,
                'nickname' => $player->nickname ?: 'Jogador '.$player->seat_number,
                'displayName' => $player->nickname ?: 'Jogador '.$player->seat_number,
                'seatNumber' => (int) $player->seat_number,
                'isBot' => (bool) $player->is_bot,
                'status' => (int) $player->stack > 0 ? 'active' : 'eliminated',
                'stack' => max(0, (int) $player->stack),
                'streetBet' => 0,
                'hasFolded' => false,
                'hasActed' => false,
                'cards' => [],
                'bestHand' => null,
            ])
            ->values()
            ->all();
    }

    private function isTournamentRuntimeTable(PokerTable $table): bool
    {
        return PokerTournament::query()
            ->where('poker_table_id', $table->id)
            ->where('status', PokerTournament::STATUS_RUNNING)
            ->exists();
    }

    private function minimumPlayersToStart(PokerTable $table): int
    {
        if (! $table->isMultiSeatCandidate()) {
            return $table->minimumPlayersToStartCurrentEngine();
        }

        return $this->seatedPlayers($table)->count() >= PokerMultiSeatEngineActivationService::MINIMUM_PLAYERS
            ? PokerMultiSeatEngineActivationService::MINIMUM_PLAYERS
            : $table->minimumPlayersToStartCurrentEngine();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function waitingSeats(PokerTable $table): array
    {
        $playersBySeat = $this->seatedPlayers($table)->keyBy('seat_number');
        $seats = [];

        for ($seatNumber = 1; $seatNumber <= $table->declaredMaxPlayers(); $seatNumber++) {
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
