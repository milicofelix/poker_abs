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
        $baseShare = intdiv($pot, count($winnerSeats));
        $remainder = $pot % count($winnerSeats);
        $payouts = [];

        foreach ($winnerSeats as $index => $seatNumber) {
            $payouts[$seatNumber] = $baseShare + ($index < $remainder ? 1 : 0);
        }

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
                    'reason' => 'Pote principal recebido no showdown.',
                    'seatNumber' => $seatNumber,
                    'winnerSeats' => $winnerSeats,
                    'pot' => $pot,
                ],
            );
        }

        $state['multiSeat']['players'] = $players;
        $state['bankrollSettlement'] = [
            'applied' => true,
            'phase' => '11.3',
            'pot' => $pot,
            'winnerSeats' => $winnerSeats,
            'payoutsBySeat' => $payouts,
            'note' => 'Pote principal liquidado no bankroll com trilha de auditoria. Side pots ficam para fase futura.',
        ];

        $state['multiSeat']['bankrollSettlementPhase'] = '11.3';

        return $state;
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
