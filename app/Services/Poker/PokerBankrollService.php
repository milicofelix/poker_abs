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

            $currentBankroll = (int) $user->poker_bankroll;

            if ($currentBankroll < $buyIn) {
                throw new DomainException(sprintf(
                    'Saldo insuficiente para sentar nesta mesa. Buy-in necessário: %d fichas.',
                    $buyIn,
                ));
            }

            $balanceAfter = $currentBankroll - $buyIn;

            $user->forceFill([
                'poker_bankroll' => $balanceAfter,
            ])->save();

            $lockedPlayer->forceFill([
                'stack' => $buyIn,
                'buy_in_amount' => $buyIn,
                'buy_in_paid_at' => now(),
            ])->save();

            $this->recordTransaction(
                user: $user,
                table: $table,
                hand: null,
                tablePlayer: $lockedPlayer,
                type: PokerBankrollTransaction::TYPE_BUY_IN,
                amount: -$buyIn,
                balanceBefore: $currentBankroll,
                balanceAfter: $balanceAfter,
                metadata: [
                    'phase' => '11.3',
                    'reason' => 'Buy-in pago ao sentar na mesa.',
                    'seatNumber' => $lockedPlayer->seat_number,
                ],
            );

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
     * Registra o crédito do pote para um jogador real vencedor.
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

        $balanceBefore = (int) $user->poker_bankroll;
        $balanceAfter = $balanceBefore + $amount;

        $user->forceFill([
            'poker_bankroll' => $balanceAfter,
        ])->save();

        $this->recordTransaction(
            user: $user,
            table: $table,
            hand: $hand,
            tablePlayer: $tablePlayer,
            type: PokerBankrollTransaction::TYPE_PAYOUT,
            amount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            metadata: [
                'phase' => '11.3',
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
