<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use PHPUnit\Framework\TestCase;

final class PokerTableCapacityTest extends TestCase
{
    public function test_mesa_padrao_continua_usando_motor_heads_up_atual(): void
    {
        $table = new PokerTable(['max_players' => 2]);

        $this->assertSame(2, $table->declaredMaxPlayers());
        $this->assertSame(2, $table->currentEngineMaxPlayers());
        $this->assertSame(2, $table->minimumPlayersToStartCurrentEngine());
        $this->assertFalse($table->isMultiSeatCandidate());
        $this->assertSame('heads_up', $table->engineMode());
    }

    public function test_mesa_com_mais_assentos_eh_mapeada_como_preparacao_multi_seat_sem_ligar_engine_3_mais(): void
    {
        $table = new PokerTable(['max_players' => 6]);

        $this->assertSame(6, $table->declaredMaxPlayers());
        $this->assertSame(2, $table->currentEngineMaxPlayers());
        $this->assertSame(2, $table->minimumPlayersToStartCurrentEngine());
        $this->assertTrue($table->isMultiSeatCandidate());
        $this->assertSame('multi_seat_preparation', $table->engineMode());
        $this->assertSame(6, $table->capacityPayload()['futureMultiSeatTarget']);
    }
}
