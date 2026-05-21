<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatBettingRoundPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatBettingRoundPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_nao_fecha_street_enquanto_existir_assento_pendente_de_acao_ou_call(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatBettingRoundPreviewService())->preview(
            $table->fresh(),
            currentBet: 80,
            committedBySeat: [1 => 80, 2 => 20, 3 => 80],
            actedBySeat: [1 => true, 2 => false, 3 => true],
        );

        $this->assertSame('10.6', $payload['phase']);
        $this->assertSame('multi_seat_betting_round.v0', $payload['schemaVersion']);
        $this->assertFalse($payload['enabledInMainEngine']);
        $this->assertFalse($payload['canCloseStreet']);
        $this->assertSame([1, 3], $payload['resolvedSeats']);
        $this->assertSame([2], $payload['pendingSeats']);
        $this->assertSame(60, $payload['seatRounds'][1]['amountToCall']);
    }

    public function test_fecha_street_quando_todos_os_assentos_ativos_agiram_e_igualaram_a_aposta(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatBettingRoundPreviewService())->preview(
            $table->fresh(),
            currentBet: 80,
            committedBySeat: [1 => 80, 2 => 80, 3 => 80],
            actedBySeat: [1 => true, 2 => true, 3 => true],
        );

        $this->assertTrue($payload['canCloseStreet']);
        $this->assertSame([1, 2, 3], $payload['resolvedSeats']);
        $this->assertSame([], $payload['pendingSeats']);
    }

    public function test_ignora_foldados_e_trata_all_in_como_resolvido(): void
    {
        $table = $this->tableWithSeats([1, 2, 3, 4]);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 2)
            ->update(['status' => 'folded']);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 4)
            ->update(['stack' => 0, 'status' => 'all_in']);

        $payload = (new PokerMultiSeatBettingRoundPreviewService())->preview(
            $table->fresh(),
            currentBet: 100,
            committedBySeat: [1 => 100, 3 => 100, 4 => 30],
            actedBySeat: [1 => true, 3 => true],
        );

        $this->assertTrue($payload['canCloseStreet']);
        $this->assertSame([1, 3, 4], array_column($payload['seatRounds'], 'seatNumber'));
        $this->assertTrue($payload['seatRounds'][2]['isAllIn']);
        $this->assertTrue($payload['seatRounds'][2]['isResolved']);
    }

    /**
     * @param array<int, int> $seats
     */
    private function tableWithSeats(array $seats): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa rodada de apostas 3+',
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
