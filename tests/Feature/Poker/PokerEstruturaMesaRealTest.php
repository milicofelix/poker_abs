<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTableSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerEstruturaMesaRealTest extends TestCase
{
    use RefreshDatabase;

    public function test_nova_mao_local_cria_assentos_reais_para_jogador_e_oponente(): void
    {
        $this->get('/poker')->assertOk();

        $table = PokerTable::query()->firstOrFail();

        $this->assertSame(2, $table->seats()->count());

        $this->assertDatabaseHas('poker_table_seats', [
            'poker_table_id' => $table->id,
            'seat_number' => 1,
            'status' => 'occupied',
            'role' => 'local_user',
            'is_dealer' => true,
            'is_small_blind' => true,
            'is_big_blind' => false,
        ]);

        $this->assertDatabaseHas('poker_table_seats', [
            'poker_table_id' => $table->id,
            'seat_number' => 2,
            'status' => 'occupied',
            'role' => 'simple_bot',
            'is_dealer' => false,
            'is_small_blind' => false,
            'is_big_blind' => true,
        ]);

        $this->assertArrayHasKey('tableSeats', session('poker.local_hand'));
        $this->assertCount(2, session('poker.local_hand.tableSeats'));
    }

    public function test_stack_dos_assentos_e_atualizado_apos_acao_da_rodada(): void
    {
        $this->get('/poker')->assertOk();

        $this->postJson('/poker/actions', [
            'action' => 'call',
        ])->assertOk();

        $this->assertDatabaseHas('poker_table_seats', [
            'seat_number' => 1,
            'stack_snapshot' => 980,
        ]);

        $this->assertDatabaseHas('poker_table_seats', [
            'seat_number' => 2,
            'stack_snapshot' => 980,
        ]);
    }

    public function test_mesa_nao_permite_dois_registros_para_o_mesmo_assento_real(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $table = PokerTable::create([
            'name' => 'Mesa local',
        ]);

        PokerTableSeat::create([
            'poker_table_id' => $table->id,
            'seat_number' => 1,
            'status' => 'empty',
        ]);

        PokerTableSeat::create([
            'poker_table_id' => $table->id,
            'seat_number' => 1,
            'status' => 'empty',
        ]);
    }
}
