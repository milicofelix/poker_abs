<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTableSeat;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LocalPokerPersistenceService
{
    public function __construct(
        private readonly PokerTurnTimerService $turnTimer,
        private readonly PokerBankrollService $bankroll,
    ) {
    }
    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function start(array $state): array
    {
        return DB::transaction(function () use ($state): array {
            $table = PokerTable::create([
                'name' => 'Mesa local',
                'status' => 'playing',
                'small_blind' => 10,
                'big_blind' => 20,
                'max_players' => PokerTable::DEFAULT_MAX_PLAYERS,
            ]);

            return $this->startOnTable($table, $state);
        });
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function startOnTable(PokerTable $table, array $state): array
    {
        return DB::transaction(function () use ($table, $state): array {
            $table->forceFill(['status' => 'playing'])->save();

            $player = $table->players()->firstOrCreate(
                ['seat' => 1],
                [
                    'name' => 'Você',
                    'type' => 'local_user',
                    'stack' => (int) ($state['playerStack'] ?? 1000),
                ],
            );

            $opponent = $table->players()->firstOrCreate(
                ['seat' => 2],
                [
                    'name' => 'Oponente',
                    'type' => 'simple_bot',
                    'stack' => (int) ($state['opponentStack'] ?? 1000),
                ],
            );

            $playerSeat = $table->seats()->updateOrCreate(
                ['seat_number' => 1],
                [
                    'poker_player_id' => $player->id,
                    'status' => 'occupied',
                    'role' => 'local_user',
                    'stack_snapshot' => (int) ($state['playerStack'] ?? $player->stack),
                    'is_dealer' => true,
                    'is_small_blind' => true,
                    'is_big_blind' => false,
                ],
            );

            $opponentSeat = $table->seats()->updateOrCreate(
                ['seat_number' => 2],
                [
                    'poker_player_id' => $opponent->id,
                    'status' => 'occupied',
                    'role' => 'simple_bot',
                    'stack_snapshot' => (int) ($state['opponentStack'] ?? $opponent->stack),
                    'is_dealer' => false,
                    'is_small_blind' => false,
                    'is_big_blind' => true,
                ],
            );

            $hand = $table->hands()->create([
                'code' => (string) Str::uuid(),
                'status' => (bool) ($state['isFinished'] ?? false) ? 'finished' : 'running',
                'street' => (string) ($state['street'] ?? 'pre_flop'),
                'pot' => (int) ($state['pot'] ?? 0),
                'current_bet' => (int) ($state['currentBet'] ?? 0),
                'dealer_position' => 1,
                ...$this->winnerAttributes($state),
                'started_at' => now(),
                'finished_at' => (bool) ($state['isFinished'] ?? false) ? now() : null,
            ]);

            $state = $this->turnTimer->start([
                ...$state,
                'tableSeats' => $this->serializeSeats([$playerSeat, $opponentSeat]),
                'currentTurn' => [
                    'actor' => 'player',
                    'actedThisStreet' => [
                        'player' => false,
                        'opponent' => false,
                    ],
                    'label' => 'Vez do jogador',
                ],
                'persistence' => [
                    'tableId' => $table->id,
                    'handId' => $hand->id,
                    'playerId' => $player->id,
                    'opponentId' => $opponent->id,
                    'playerSeatId' => $playerSeat->id,
                    'opponentSeatId' => $opponentSeat->id,
                    'loggedActions' => 0,
                    'syncVersion' => 1,
                ],
            ]);

            $hand->forceFill(['state_payload' => $state])->save();

            return $state;
        });
    }


    /**
     * Persiste uma mão multi-seat controlada na mesa sem reutilizar o contrato heads-up.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function startMultiSeatOnTable(PokerTable $table, array $state): array
    {
        return DB::transaction(function () use ($table, $state): array {
            $this->closeTerminalRunningHandsForTable($table);

            $table->forceFill(['status' => 'playing'])->save();

            $players = $state['multiSeat']['players'] ?? [];
            $players = is_array($players) ? array_values(array_filter(
                $players,
                static fn (mixed $player): bool => is_array($player),
            )) : [];

            $seats = [];

            foreach ($players as $player) {
                $seatNumber = (int) ($player['seatNumber'] ?? 0);
                $tablePlayerId = (int) ($player['tablePlayerId'] ?? 0);

                if ($seatNumber <= 0 || $tablePlayerId <= 0) {
                    continue;
                }

                $seats[] = $table->seats()->updateOrCreate(
                    ['seat_number' => $seatNumber],
                    [
                        'poker_player_id' => null,
                        'status' => 'occupied',
                        'role' => (bool) ($player['isBot'] ?? false) ? 'simple_bot' : 'real_user',
                        'stack_snapshot' => (int) ($player['stack'] ?? 0),
                        'is_dealer' => $seatNumber === (int) data_get($state, 'multiSeat.dealerSeat', 1),
                        'is_small_blind' => $seatNumber === (int) data_get($state, 'multiSeat.smallBlindSeat', 2),
                        'is_big_blind' => $seatNumber === (int) data_get($state, 'multiSeat.bigBlindSeat', 3),
                    ],
                );
            }

            $hand = $table->hands()->create([
                'code' => (string) Str::uuid(),
                'status' => (bool) ($state['isFinished'] ?? false) ? 'finished' : 'running',
                'street' => (string) ($state['street'] ?? 'pre_flop'),
                'pot' => (int) ($state['pot'] ?? 0),
                'current_bet' => (int) ($state['currentBet'] ?? 0),
                'dealer_position' => (int) data_get($state, 'multiSeat.dealerSeat', 1),
                ...$this->winnerAttributes($state),
                'started_at' => now(),
                'finished_at' => (bool) ($state['isFinished'] ?? false) ? now() : null,
            ]);

            $state = $this->turnTimer->start([
                ...$state,
                'tableSeats' => $this->serializeSeats($seats),
                'persistence' => [
                    'multiSeat' => true,
                    'tableId' => $table->id,
                    'handId' => $hand->id,
                    'loggedActions' => 0,
                    'syncVersion' => 1,
                ],
            ]);

            $hand->forceFill(['state_payload' => $state])->save();
            $this->syncMultiSeatStacks($state);

            return $state;
        });
    }

    /**
     * Fecha mãos antigas que ficaram com status running, mas payload terminal.
     *
     * Esse cenário aparece principalmente no torneio quando a mão termina em
     * showdown/all-in, o frontend reidrata o estado final e o jogador aciona
     * "Iniciar nova mão". Se a mão antiga continuar marcada como running,
     * o polling pode voltar para o snapshot final e parecer que o botão não fez
     * nada.
     */
    public function closeTerminalRunningHandsForTable(PokerTable $table): void
    {
        PokerHand::query()
            ->where('poker_table_id', $table->id)
            ->where('status', 'running')
            ->orderBy('id')
            ->get()
            ->each(static function (PokerHand $hand): void {
                $payload = $hand->state_payload;

                if (! is_array($payload) || ! (bool) ($payload['isFinished'] ?? false)) {
                    return;
                }

                $hand->forceFill([
                    'status' => 'finished',
                    'street' => (string) ($payload['street'] ?? $hand->street ?? 'showdown'),
                    'pot' => (int) ($payload['pot'] ?? $hand->pot ?? 0),
                    'current_bet' => (int) ($payload['currentBet'] ?? $hand->current_bet ?? 0),
                    'finished_at' => $hand->finished_at ?? now(),
                ])->save();
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function currentStateForTable(PokerTable $table): ?array
    {
        /** @var PokerHand|null $hand */
        $hand = $table->hands()
            ->where('status', 'running')
            ->latest('id')
            ->first();

        if (! $hand) {
            return null;
        }

        return is_array($hand->state_payload)
            ? $this->turnTimer->refresh($hand->state_payload)
            : null;
    }

    /**
     * Retorna o último estado gravado da mesa, mesmo quando a mão já foi finalizada.
     *
     * Diferente de currentStateForTable(), este método é usado na reidratação
     * da mesa para evitar que uma mão finalizada seja substituída automaticamente
     * por uma nova mão durante polling/refresh do estado.
     *
     * @return array<string, mixed>|null
     */
    public function latestStateForTable(PokerTable $table): ?array
    {
        /** @var PokerHand|null $hand */
        $hand = $table->hands()
            ->latest('id')
            ->first();

        if (! $hand || ! is_array($hand->state_payload)) {
            return null;
        }

        if ($hand->status === 'running' && ! (bool) ($hand->state_payload['isFinished'] ?? false)) {
            return $this->turnTimer->refresh($hand->state_payload);
        }

        return $hand->state_payload;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function persist(array $state): array
    {
        $state = $this->hasPersistence($state) ? $state : $this->start($state);

        return DB::transaction(function () use ($state): array {
            $persistence = $state['persistence'];

            /** @var PokerHand $hand */
            $hand = PokerHand::query()->findOrFail((int) $persistence['handId']);

            $state['persistence']['syncVersion'] = max(1, (int) ($state['persistence']['syncVersion'] ?? 1)) + 1;

            if ((bool) ($state['isFinished'] ?? false)) {
                unset($state['turnTimer']);
            } else {
                $state = $this->turnTimer->start($state);
            }

            if ((bool) ($persistence['multiSeat'] ?? false)) {
                $state = $this->settleMultiSeatBankrollIfNeeded($hand, $state);
            }

            $hand->forceFill([
                'status' => (bool) ($state['isFinished'] ?? false) ? 'finished' : 'running',
                'street' => (string) ($state['street'] ?? 'pre_flop'),
                'pot' => (int) ($state['pot'] ?? 0),
                'current_bet' => (int) ($state['currentBet'] ?? 0),
                'state_payload' => $state,
                ...$this->winnerAttributes($state),
                'finished_at' => (bool) ($state['isFinished'] ?? false)
                    ? ($hand->finished_at ?? now())
                    : null,
            ])->save();

            if ((bool) ($persistence['multiSeat'] ?? false)) {
                $this->syncMultiSeatStacks($state);
            } else {
                $playerStack = (int) ($state['playerStack'] ?? 1000);
                $opponentStack = (int) ($state['opponentStack'] ?? 1000);

                PokerPlayer::query()
                    ->whereKey((int) $persistence['playerId'])
                    ->update(['stack' => $playerStack]);

                PokerPlayer::query()
                    ->whereKey((int) $persistence['opponentId'])
                    ->update(['stack' => $opponentStack]);

                PokerTableSeat::query()
                    ->whereKey((int) ($persistence['playerSeatId'] ?? 0))
                    ->update(['stack_snapshot' => $playerStack]);

                PokerTableSeat::query()
                    ->whereKey((int) ($persistence['opponentSeatId'] ?? 0))
                    ->update(['stack_snapshot' => $opponentStack]);
            }

            $state['tableSeats'] = $this->serializeSeats(
                PokerTableSeat::query()
                    ->where('poker_table_id', (int) $persistence['tableId'])
                    ->orderBy('seat_number')
                    ->get()
                    ->all(),
            );

            $loggedActions = max(0, (int) ($persistence['loggedActions'] ?? 0));
            $history = $this->normalizeHistory($state['actionHistory'] ?? []);
            $pendingActions = array_slice($history, $loggedActions);

            foreach ($pendingActions as $action) {
                PokerActionLog::create([
                    'poker_hand_id' => $hand->id,
                    'poker_player_id' => $this->resolvePlayerId($action, $persistence),
                    'street' => (string) ($action['street'] ?? $state['street'] ?? 'pre_flop'),
                    'action' => (string) ($action['action'] ?? 'Ação'),
                    'amount' => max(0, (int) ($action['amount'] ?? 0)),
                    'pot_after_action' => max(0, (int) ($action['pot'] ?? $state['pot'] ?? 0)),
                    'metadata' => [
                        'actor' => (string) ($action['actor'] ?? 'unknown'),
                        'message' => (string) ($action['message'] ?? ''),
                        'player_stack' => Arr::get($action, 'playerStack'),
                        'opponent_stack' => Arr::get($action, 'opponentStack'),
                    ],
                    'acted_at' => now(),
                ]);
            }

            $state['persistence']['loggedActions'] = count($history);

            $hand->forceFill(['state_payload' => $state])->save();

            return $state;
        });
    }

    /**
     * @param array<string, mixed> $state
     */
    private function hasPersistence(array $state): bool
    {
        if (! isset($state['persistence']['tableId'], $state['persistence']['handId'])) {
            return false;
        }

        if ((bool) ($state['persistence']['multiSeat'] ?? false)) {
            return true;
        }

        return isset(
            $state['persistence']['playerId'],
            $state['persistence']['opponentId'],
            $state['persistence']['playerSeatId'],
            $state['persistence']['opponentSeatId'],
        );
    }

    /**
     * @param mixed $history
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHistory(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter(
            $history,
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    /**
     * @param array<string, mixed> $action
     * @param array<string, mixed> $persistence
     */
    private function resolvePlayerId(array $action, array $persistence): ?int
    {
        if ((bool) ($persistence['multiSeat'] ?? false)) {
            return null;
        }

        return ($action['actor'] ?? null) === 'opponent'
            ? (int) $persistence['opponentId']
            : (int) $persistence['playerId'];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, string|null>
     */
    private function winnerAttributes(array $state): array
    {
        $winner = Arr::get($state, 'conclusion.winner');

        if (! is_array($winner)) {
            return [
                'winner' => null,
                'winner_label' => null,
                'winning_hand_name' => null,
            ];
        }

        return [
            'winner' => isset($winner['player']) ? (string) $winner['player'] : null,
            'winner_label' => isset($winner['label']) ? (string) $winner['label'] : null,
            'winning_hand_name' => isset($winner['handName']) ? (string) $winner['handName'] : null,
        ];
    }


    /**
     * Aplica o resultado financeiro da mão multi-seat uma única vez.
     *
     * A FASE 11.2 ainda não implementa side pots completos; por enquanto o pote
     * principal é dividido igualmente entre os assentos vencedores informados
     * pelo motor da FASE 10.12.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function settleMultiSeatBankrollIfNeeded(PokerHand $hand, array $state): array
    {
        if (! (bool) ($state['isFinished'] ?? false)) {
            return $state;
        }

        if (data_get($state, 'bankrollSettlement.applied') === true) {
            return $state;
        }

        if ($hand->state_payload && data_get($hand->state_payload, 'bankrollSettlement.applied') === true) {
            $state['bankrollSettlement'] = $hand->state_payload['bankrollSettlement'];

            return $state;
        }

        $players = data_get($state, 'multiSeat.players', []);

        if (! is_array($players) || $players === []) {
            return $state;
        }

        $winnerSeats = array_values(array_filter(array_map(
            'intval',
            (array) data_get($state, 'multiSeat.winnerSeats', []),
        )));

        if ($winnerSeats === []) {
            $winnerSeat = (int) data_get($state, 'conclusion.winner.seatNumber', 0);

            if ($winnerSeat > 0) {
                $winnerSeats = [$winnerSeat];
            }
        }

        $winnerSeats = array_values(array_unique($winnerSeats));
        sort($winnerSeats);

        if ($winnerSeats === []) {
            return $state;
        }

        $pot = max(0, (int) ($state['pot'] ?? 0));
        $sidePotSettlement = $this->buildMultiSeatSidePotSettlement($state, $winnerSeats, $pot);
        $payouts = $sidePotSettlement['payoutsBySeat'];

        foreach ($players as $index => $player) {
            if (! is_array($player)) {
                continue;
            }

            $seatNumber = (int) ($player['seatNumber'] ?? 0);
            $payout = (int) ($payouts[$seatNumber] ?? 0);

            if ($payout <= 0) {
                continue;
            }

            $players[$index]['stack'] = max(0, (int) ($player['stack'] ?? 0)) + $payout;
            $players[$index]['lastPayout'] = $payout;

            $tablePlayerId = (int) ($player['tablePlayerId'] ?? 0);

            if ($tablePlayerId <= 0 || (bool) ($player['isBot'] ?? false)) {
                continue;
            }

            /** @var \App\Models\Poker\PokerTablePlayer|null $tablePlayer */
            $tablePlayer = \App\Models\Poker\PokerTablePlayer::query()
                ->whereKey($tablePlayerId)
                ->lockForUpdate()
                ->first();

            if (! $tablePlayer || ! $tablePlayer->user_id) {
                continue;
            }

            /** @var User|null $user */
            $user = User::query()
                ->whereKey($tablePlayer->user_id)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                continue;
            }

            $this->bankroll->creditPayout(
                user: $user,
                table: $hand->table,
                hand: $hand,
                tablePlayer: $tablePlayer,
                amount: $payout,
                metadata: [
                    'reason' => 'Pote principal/lateral recebido no showdown.',
                    'seatNumber' => $seatNumber,
                    'winnerSeats' => $winnerSeats,
                    'pot' => $pot,
                ],
            );
        }

        $state['multiSeat']['players'] = $players;
        $state['bankrollSettlement'] = [
            'applied' => true,
            'phase' => '12.6',
            'pot' => $pot,
            'winnerSeats' => $winnerSeats,
            'payoutsBySeat' => $payouts,
            'sidePots' => $sidePotSettlement['sidePots'],
            'note' => $sidePotSettlement['hasSidePot']
                ? 'Potes principal/laterais liquidados com base nas contribuições por assento.'
                : 'Pote principal liquidado no stack da mesa com trilha de auditoria.',
        ];

        $state['multiSeat']['sidePots'] = $sidePotSettlement['sidePots'];
        $state['multiSeat']['bankrollSettlementPhase'] = '12.6';

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @param array<int, int> $winnerSeats
     * @return array{payoutsBySeat: array<int, int>, sidePots: array<string, mixed>, hasSidePot: bool}
     */
    private function buildMultiSeatSidePotSettlement(array $state, array $winnerSeats, int $fallbackPot): array
    {
        $contributionSeats = (array) data_get($state, 'multiSeat.contributions.seats', []);
        $players = data_get($state, 'multiSeat.players', []);
        $foldedSeats = [];

        if (is_array($players)) {
            foreach ($players as $player) {
                if (is_array($player) && (bool) ($player['hasFolded'] ?? false)) {
                    $foldedSeats[] = (int) ($player['seatNumber'] ?? 0);
                }
            }
        }

        $totals = [];
        $playersBySeat = [];

        if (is_array($players)) {
            foreach ($players as $player) {
                if (! is_array($player)) {
                    continue;
                }

                $seatNumber = (int) ($player['seatNumber'] ?? 0);

                if ($seatNumber > 0) {
                    $playersBySeat[$seatNumber] = $player;
                }
            }
        }

        foreach ($contributionSeats as $seatKey => $seatContribution) {
            if (! is_array($seatContribution)) {
                continue;
            }

            $seatNumber = (int) ($seatContribution['seatNumber'] ?? $seatKey);
            $total = max(0, (int) ($seatContribution['total'] ?? 0));

            if ($seatNumber > 0 && $total > 0) {
                $totals[$seatNumber] = $total;
            }
        }

        if ($totals === []) {
            return $this->buildFallbackMainPotSettlement($winnerSeats, $fallbackPot);
        }

        $levels = array_values(array_unique(array_values($totals)));
        sort($levels);

        $payouts = [];
        $pots = [];
        $previousLevel = 0;

        foreach ($levels as $level) {
            $contributors = array_values(array_keys(array_filter(
                $totals,
                static fn (int $total): bool => $total >= $level,
            )));

            $amount = ($level - $previousLevel) * count($contributors);

            if ($amount <= 0) {
                $previousLevel = $level;
                continue;
            }

            $eligibleSeats = array_values(array_filter(
                $contributors,
                static fn (int $seatNumber): bool => ! in_array($seatNumber, $foldedSeats, true),
            ));
            sort($contributors);
            sort($eligibleSeats);

            $potWinnerSeats = $this->winnerSeatsForSidePot(
                eligibleSeats: $eligibleSeats,
                globalWinnerSeats: $winnerSeats,
                playersBySeat: $playersBySeat,
            );

            sort($potWinnerSeats);
            $baseShare = intdiv($amount, max(1, count($potWinnerSeats)));
            $remainder = $amount % max(1, count($potWinnerSeats));
            $potPayouts = [];

            foreach ($potWinnerSeats as $index => $seatNumber) {
                $share = $baseShare + ($index < $remainder ? 1 : 0);
                $payouts[$seatNumber] = (int) ($payouts[$seatNumber] ?? 0) + $share;
                $potPayouts[$seatNumber] = $share;
            }

            $pots[] = [
                'index' => count($pots) + 1,
                'type' => $pots === [] ? 'main' : 'side',
                'isMainPot' => $pots === [],
                'isSidePot' => $pots !== [],
                'amount' => $amount,
                'cap' => $level,
                'allInCap' => $level,
                'contributors' => $contributors,
                'eligibleSeats' => $eligibleSeats,
                'winnerSeats' => $potWinnerSeats,
                'payoutsBySeat' => $potPayouts,
            ];

            $previousLevel = $level;
        }

        return [
            'payoutsBySeat' => $payouts,
            'sidePots' => [
                'phase' => '12.6',
                'ready' => true,
                'hasSidePot' => count($pots) > 1,
                'pots' => $pots,
                'allInLevels' => $levels,
                'oddChipPolicy' => 'odd_chip_to_lowest_seat_among_pot_winners',
                'total' => array_sum(array_map(static fn (array $pot): int => (int) $pot['amount'], $pots)),
            ],
            'hasSidePot' => count($pots) > 1,
        ];
    }

    /**
     * Seleciona vencedores por pote.
     *
     * A FASE 12.6 permite que all-ins em cascata criem múltiplos potes elegíveis
     * diferentes. Quando há mãos avaliadas no estado, o vencedor de cada pote
     * é calculado apenas entre os assentos elegíveis daquele pote. Quando o
     * estado não possui ranking suficiente, preserva o fallback antigo baseado
     * em winnerSeats globais.
     *
     * @param array<int, int> $eligibleSeats
     * @param array<int, int> $globalWinnerSeats
     * @param array<int, array<string, mixed>> $playersBySeat
     * @return array<int, int>
     */
    private function winnerSeatsForSidePot(array $eligibleSeats, array $globalWinnerSeats, array $playersBySeat): array
    {
        $eligibleSeats = array_values(array_filter(array_map('intval', $eligibleSeats)));
        sort($eligibleSeats);

        if ($eligibleSeats === []) {
            return $globalWinnerSeats;
        }

        $rankedSeats = [];

        foreach ($eligibleSeats as $seatNumber) {
            $player = $playersBySeat[$seatNumber] ?? null;

            if (! is_array($player)) {
                continue;
            }

            $hand = $this->sidePotHandPayload($player);

            if ($hand === null) {
                continue;
            }

            $rankedSeats[$seatNumber] = $hand;
        }

        if ($rankedSeats !== []) {
            $bestSeat = array_key_first($rankedSeats);
            $bestHand = $rankedSeats[$bestSeat];

            foreach ($rankedSeats as $seatNumber => $hand) {
                if ($this->compareSidePotHands($hand, $bestHand) > 0) {
                    $bestSeat = $seatNumber;
                    $bestHand = $hand;
                }
            }

            return array_values(array_filter(
                array_keys($rankedSeats),
                fn (int $seatNumber): bool => $this->compareSidePotHands($rankedSeats[$seatNumber], $bestHand) === 0,
            ));
        }

        $intersectedWinners = array_values(array_intersect($globalWinnerSeats, $eligibleSeats));

        if ($intersectedWinners !== []) {
            return $intersectedWinners;
        }

        return [$eligibleSeats[0]];
    }

    /**
     * @param array<string, mixed> $player
     * @return array<string, mixed>|null
     */
    private function sidePotHandPayload(array $player): ?array
    {
        foreach (['showdownHand', 'bestHand'] as $key) {
            $hand = $player[$key] ?? null;

            if (is_array($hand) && array_key_exists('rank', $hand)) {
                return [
                    'rank' => (int) ($hand['rank'] ?? 0),
                    'kickers' => array_values(array_map('intval', (array) ($hand['kickers'] ?? []))),
                ];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareSidePotHands(array $left, array $right): int
    {
        $rankComparison = (int) ($left['rank'] ?? 0) <=> (int) ($right['rank'] ?? 0);

        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        $leftKickers = array_values(array_map('intval', (array) ($left['kickers'] ?? [])));
        $rightKickers = array_values(array_map('intval', (array) ($right['kickers'] ?? [])));
        $max = max(count($leftKickers), count($rightKickers));

        for ($index = 0; $index < $max; $index++) {
            $comparison = ($leftKickers[$index] ?? 0) <=> ($rightKickers[$index] ?? 0);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    /**
     * @param array<int, int> $winnerSeats
     * @return array{payoutsBySeat: array<int, int>, sidePots: array<string, mixed>, hasSidePot: bool}
     */
    private function buildFallbackMainPotSettlement(array $winnerSeats, int $pot): array
    {
        $baseShare = intdiv($pot, max(1, count($winnerSeats)));
        $remainder = $pot % max(1, count($winnerSeats));
        $payouts = [];

        foreach ($winnerSeats as $index => $seatNumber) {
            $payouts[$seatNumber] = $baseShare + ($index < $remainder ? 1 : 0);
        }

        return [
            'payoutsBySeat' => $payouts,
            'sidePots' => [
                'phase' => '12.6',
                'ready' => false,
                'hasSidePot' => false,
                'pots' => [[
                    'index' => 1,
                    'type' => 'main',
                    'isMainPot' => true,
                    'isSidePot' => false,
                    'amount' => $pot,
                    'cap' => null,
                    'allInCap' => null,
                    'contributors' => [],
                    'eligibleSeats' => $winnerSeats,
                    'winnerSeats' => $winnerSeats,
                    'payoutsBySeat' => $payouts,
                ]],
                'allInLevels' => [],
                'oddChipPolicy' => 'odd_chip_to_lowest_seat_among_pot_winners',
                'total' => $pot,
            ],
            'hasSidePot' => false,
        ];
    }


    /**
     * @param array<string, mixed> $state
     */
    private function syncMultiSeatStacks(array $state): void
    {
        $players = $state['multiSeat']['players'] ?? [];

        if (! is_array($players)) {
            return;
        }

        foreach ($players as $player) {
            if (! is_array($player) || ! isset($player['tablePlayerId'])) {
                continue;
            }

            $stack = max(0, (int) ($player['stack'] ?? 0));
            $tablePlayerId = (int) $player['tablePlayerId'];

            \App\Models\Poker\PokerTablePlayer::query()
                ->whereKey($tablePlayerId)
                ->update(['stack' => $stack]);
        }
    }

    /**
     * @param array<int, PokerTableSeat> $seats
     * @return array<int, array<string, mixed>>
     */
    private function serializeSeats(array $seats): array
    {
        return array_map(
            static fn (PokerTableSeat $seat): array => [
                'id' => $seat->id,
                'seatNumber' => $seat->seat_number,
                'status' => $seat->status,
                'role' => $seat->role,
                'playerId' => $seat->poker_player_id,
                'stack' => $seat->stack_snapshot,
                'isDealer' => $seat->is_dealer,
                'isSmallBlind' => $seat->is_small_blind,
                'isBigBlind' => $seat->is_big_blind,
            ],
            $seats,
        );
    }
}
