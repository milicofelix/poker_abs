<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatTurnCyclePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatTurnCyclePreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monta_fila_circular_para_tres_jogadores_a_partir_do_big_blind(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatTurnCyclePreviewService())->preview($table->fresh(), dealerSeat: 1);

        $this->assertSame('10.4', $payload['phase']);
        $this->assertFalse($payload['enabledInMainEngine']);
        $this->assertSame(1, $payload['dealerSeat']);
        $this->assertSame(2, $payload['smallBlindSeat']);
        $this->assertSame(3, $payload['bigBlindSeat']);
        $this->assertSame(1, $payload['firstPreFlopSeat']);
        $this->assertSame(2, $payload['firstPostFlopSeat']);
        $this->assertSame([2, 3, 1], array_column($payload['actionOrder'], 'seatNumber'));
    }

    public function test_fila_circular_ignora_assentos_vazios_foldados_ou_sem_stack(): void
    {
        $table = $this->tableWithSeats([1, 2, 4, 6]);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 4)
            ->update(['status' => 'folded']);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 6)
            ->update(['stack' => 0]);

        $payload = (new PokerMultiSeatTurnCyclePreviewService())->preview($table->fresh(), dealerSeat: 1, currentSeat: 1);

        $this->assertSame(2, $payload['activeSeatedPlayers']);
        $this->assertSame(2, $payload['nextSeatToAct']);
        $this->assertSame([2, 1], array_column($payload['actionOrder'], 'seatNumber'));
    }

    /**
     * @param array<int, int> $seats
     */
    private function tableWithSeats(array $seats): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa ciclo 3+',
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
