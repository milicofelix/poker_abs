<?php

namespace App\Services\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class PokerBankrollService
{
    public function tableBuyIn(PokerTable $table): int
    {
        return $table->buyInAmount();
    }

    public function payBuyInForSeat(PokerTable $table, PokerTablePlayer $player): PokerTablePlayer
    {
        if ((bool) $player->is_bot) {
            return $this->markBotBuyInAsPaid($table, $player);
        }

        if ($player->buy_in_paid_at !== null) {
            return $player;
        }

        $buyIn = $this->tableBuyIn($table);

        return DB::transaction(function () use ($table, $player, $buyIn): PokerTablePlayer {
            /** @var PokerTablePlayer $lockedPlayer */
            $lockedPlayer = PokerTablePlayer::query()
                ->whereKey($player->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPlayer->buy_in_paid_at !== null) {
                return $lockedPlayer->fresh();
            }

            /** @var User $user */
            $user = User::query()
                ->whereKey($lockedPlayer->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->debitUserToTableStack(
                table: $table,
                user: $user,
                tablePlayer: $lockedPlayer,
                amount: $buyIn,
                type: PokerBankrollTransaction::TYPE_BUY_IN,
                metadata: [
                    'phase' => '12.1',
                    'reason' => 'Buy-in pago ao sentar na mesa.',
                    'seatNumber' => $lockedPlayer->seat_number,
                ],
            );

            $lockedPlayer->forceFill([
                'buy_in_amount' => $buyIn,
                'buy_in_paid_at' => now(),
            ])->save();

            return $lockedPlayer->fresh();
        });
    }

    private function markBotBuyInAsPaid(PokerTable $table, PokerTablePlayer $player): PokerTablePlayer
    {
        if ($player->buy_in_paid_at !== null) {
            return $player;
        }

        $buyIn = $this->tableBuyIn($table);

        $player->forceFill([
            'stack' => $buyIn,
            'buy_in_amount' => $buyIn,
            'buy_in_paid_at' => now(),
        ])->save();

        return $player->fresh();
    }

    /**
     * Registra o crédito do pote no stack da mesa.
     *
     * Na FASE 12.1, ganhos de mão permanecem no stack da mesa. O bankroll global
     * só recebe fichas novamente quando o jogador sai da mesa.
     *
     * @param array<string, mixed> $metadata
     */
    public function creditPayout(
        User $user,
        PokerTable $table,
        PokerHand $hand,
        PokerTablePlayer $tablePlayer,
        int $amount,
        array $metadata = [],
    ): void {
        if ($amount <= 0) {
            return;
        }

        $balance = (int) $user->poker_bankroll;
        $stackBefore = (int) $tablePlayer->stack;
        $stackAfter = $stackBefore + $amount;

        $tablePlayer->forceFill([
            'stack' => $stackAfter,
        ])->save();

        $this->recordTransaction(
            user: $user,
            table: $table,
            hand: $hand,
            tablePlayer: $tablePlayer,
            type: PokerBankrollTransaction::TYPE_PAYOUT,
            amount: $amount,
            balanceBefore: $balance,
            balanceAfter: $balance,
            metadata: [
                'phase' => '12.1',
                'settlementTarget' => 'table_stack',
                'stackBefore' => $stackBefore,
                'stackAfter' => $stackAfter,
                ...$metadata,
            ],
        );
    }

    public function rebuy(PokerTable $table, PokerTablePlayer $player, ?int $amount = null): PokerTablePlayer
    {
        if ((bool) $player->is_bot) {
            throw new DomainException('Rebuy manual não se aplica a bots.');
        }

        $rebuyAmount = $amount ?? $this->tableBuyIn($table);

        if ($rebuyAmount <= 0) {
            throw new DomainException('Valor de rebuy inválido.');
        }

        return DB::transaction(function () use ($table, $player, $rebuyAmount): PokerTablePlayer {
            /** @var PokerTablePlayer $lockedPlayer */
            $lockedPlayer = PokerTablePlayer::query()
                ->whereKey($player->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPlayer->left_at !== null || $lockedPlayer->seat_number === null) {
                throw new DomainException('Entre e sente na mesa antes de fazer rebuy.');
            }

            /** @var User $user */
            $user = User::query()
                ->whereKey($lockedPlayer->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->debitUserToTableStack(
                table: $table,
                user: $user,
                tablePlayer: $lockedPlayer,
                amount: $rebuyAmount,
                type: PokerBankrollTransaction::TYPE_REBUY,
                metadata: [
                    'phase' => '12.1',
                    'reason' => 'Rebuy adicionado ao stack da mesa.',
                    'seatNumber' => $lockedPlayer->seat_number,
                ],
            );

            $lockedPlayer->forceFill([
                'buy_in_amount' => (int) $lockedPlayer->buy_in_amount + $rebuyAmount,
            ])->save();

            return $lockedPlayer->fresh();
        });
    }

    public function returnStackToBankroll(PokerTable $table, PokerTablePlayer $player): PokerTablePlayer
    {
        if ((bool) $player->is_bot || ! $player->user_id) {
            $player->forceFill([
                'stack' => 0,
                'status' => 'offline',
                'seat_number' => null,
                'buy_in_amount' => 0,
                'buy_in_paid_at' => null,
                'left_at' => now(),
            ])->save();

            return $player->fresh();
        }

        return DB::transaction(function () use ($table, $player): PokerTablePlayer {
            /** @var PokerTablePlayer $lockedPlayer */
            $lockedPlayer = PokerTablePlayer::query()
                ->whereKey($player->id)
                ->lockForUpdate()
                ->firstOrFail();

            $stackToReturn = max(0, (int) $lockedPlayer->stack);

            /** @var User $user */
            $user = User::query()
                ->whereKey($lockedPlayer->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stackToReturn > 0 && $lockedPlayer->left_at === null) {
                $balanceBefore = (int) $user->poker_bankroll;
                $balanceAfter = $balanceBefore + $stackToReturn;

                $user->forceFill([
                    'poker_bankroll' => $balanceAfter,
                ])->save();

                $this->recordTransaction(
                    user: $user,
                    table: $table,
                    hand: null,
                    tablePlayer: $lockedPlayer,
                    type: PokerBankrollTransaction::TYPE_STACK_RETURN,
                    amount: $stackToReturn,
                    balanceBefore: $balanceBefore,
                    balanceAfter: $balanceAfter,
                    metadata: [
                        'phase' => '12.1',
                        'reason' => 'Stack devolvido ao bankroll ao sair da mesa.',
                        'seatNumber' => $lockedPlayer->seat_number,
                    ],
                );
            }

            $lockedPlayer->forceFill([
                'stack' => 0,
                'status' => 'offline',
                'seat_number' => null,
                'buy_in_amount' => 0,
                'buy_in_paid_at' => null,
                'left_at' => now(),
            ])->save();

            return $lockedPlayer->fresh();
        });
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function debitUserToTableStack(
        PokerTable $table,
        User $user,
        PokerTablePlayer $tablePlayer,
        int $amount,
        string $type,
        array $metadata = [],
    ): void {
        $currentBankroll = (int) $user->poker_bankroll;

        if ($currentBankroll < $amount) {
            throw new DomainException(sprintf(
                'Saldo insuficiente para sentar nesta mesa. Buy-in necessário: %d fichas.',
                $amount,
            ));
        }

        $balanceAfter = $currentBankroll - $amount;
        $stackBefore = (int) $tablePlayer->stack;
        $stackAfter = $type === PokerBankrollTransaction::TYPE_BUY_IN
            ? $amount
            : $stackBefore + $amount;

        $user->forceFill([
            'poker_bankroll' => $balanceAfter,
        ])->save();

        $tablePlayer->forceFill([
            'stack' => $stackAfter,
        ])->save();

        $this->recordTransaction(
            user: $user,
            table: $table,
            hand: null,
            tablePlayer: $tablePlayer,
            type: $type,
            amount: -$amount,
            balanceBefore: $currentBankroll,
            balanceAfter: $balanceAfter,
            metadata: [
                'stackBefore' => $stackBefore,
                'stackAfter' => $stackAfter,
                ...$metadata,
            ],
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function recordTransaction(
        User $user,
        PokerTable $table,
        ?PokerHand $hand,
        PokerTablePlayer $tablePlayer,
        string $type,
        int $amount,
        int $balanceBefore,
        int $balanceAfter,
        array $metadata = [],
    ): void {
        PokerBankrollTransaction::query()->create([
            'user_id' => $user->id,
            'poker_table_id' => $table->id,
            'poker_hand_id' => $hand?->id,
            'poker_table_player_id' => $tablePlayer->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'metadata' => $metadata,
        ]);
    }
}
