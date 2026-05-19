<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatActionPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatActionPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapeia_check_call_raise_e_fold_para_assento_atual_sem_ligar_motor_principal(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatActionPreviewService())->preview(
            $table->fresh(),
            currentSeat: 2,
            currentBet: 80,
            minimumRaise: 40,
            committedBySeat: [1 => 80, 2 => 20, 3 => 80],
            actedBySeat: [1 => true, 2 => false, 3 => true],
        );

        $this->assertSame('10.5', $payload['phase']);
        $this->assertFalse($payload['enabledInMainEngine']);
        $this->assertSame(2, $payload['currentSeat']);
        $this->assertSame(60, $payload['amountToCall']);
        $this->assertFalse($payload['canCheck']);
        $this->assertTrue($payload['canCall']);
        $this->assertTrue($payload['canRaise']);
        $this->assertTrue($payload['canFold']);
        $this->assertFalse($payload['streetIsComplete']);
        $this->assertSame([1, 2, 3], array_column($payload['seatActions'], 'seatNumber'));
        $this->assertSame(60, $payload['seatActions'][1]['amountToCall']);
    }

    public function test_identifica_street_completa_quando_todos_agiram_e_pagaram_a_aposta(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatActionPreviewService())->preview(
            $table->fresh(),
            currentSeat: 1,
            currentBet: 80,
            minimumRaise: 40,
            committedBySeat: [1 => 80, 2 => 80, 3 => 80],
            actedBySeat: [1 => true, 2 => true, 3 => true],
        );

        $this->assertTrue($payload['streetIsComplete']);
        $this->assertTrue($payload['canCheck']);
        $this->assertFalse($payload['canCall']);
    }

    public function test_ignora_assentos_foldados_ou_sem_stack_no_preview_de_acoes(): void
    {
        $table = $this->tableWithSeats([1, 2, 4]);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 2)
            ->update(['status' => 'folded']);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 4)
            ->update(['stack' => 0]);

        $payload = (new PokerMultiSeatActionPreviewService())->preview($table->fresh(), currentSeat: 2);

        $this->assertSame(1, $payload['currentSeat']);
        $this->assertSame([1], array_column($payload['seatActions'], 'seatNumber'));
    }

    /**
     * @param array<int, int> $seats
     */
    private function tableWithSeats(array $seats): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa ações 3+',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        foreach ($seats as $seat) {
            $user = User::factory()->create();

            PokerTablePlayer::query()->create([
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
                'nickname' => "Jogador {$seat}",
                'stack' => 1000,
                'seat_number' => $seat,
                'status' => 'active',
                'joined_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        return $table;
    }
}
