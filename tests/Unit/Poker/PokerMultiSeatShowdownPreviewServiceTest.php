<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatShowdownPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatShowdownPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordena_vencedor_do_showdown_tres_mais_por_forca_da_mao(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatShowdownPreviewService())->preview($table->fresh(), [
            1 => ['name' => 'Par', 'rank' => 2, 'kickers' => [14, 9, 7]],
            2 => ['name' => 'Dois pares', 'rank' => 3, 'kickers' => [13, 12, 8]],
            3 => ['name' => 'Carta alta', 'rank' => 1, 'kickers' => [14, 11, 8]],
        ]);

        $this->assertSame('10.7', $payload['phase']);
        $this->assertSame('multi_seat_showdown.v0', $payload['schemaVersion']);
        $this->assertFalse($payload['enabledInMainEngine']);
        $this->assertTrue($payload['canResolveShowdown']);
        $this->assertSame(3, $payload['activeContenders']);
        $this->assertSame([2], $payload['winnerSeats']);
        $this->assertFalse($payload['isSplitPot']);
        $this->assertSame(2, $payload['ranking'][0]['seatNumber']);
        $this->assertSame('Dois pares', $payload['ranking'][0]['handName']);
    }

    public function test_detecta_empate_e_pote_dividido_entre_multiplos_assentos(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatShowdownPreviewService())->preview($table->fresh(), [
            1 => ['name' => 'Sequência', 'rank' => 5, 'kickers' => [10]],
            2 => ['name' => 'Sequência', 'rank' => 5, 'kickers' => [10]],
            3 => ['name' => 'Trinca', 'rank' => 4, 'kickers' => [14, 8]],
        ]);

        $this->assertSame([1, 2], $payload['winnerSeats']);
        $this->assertTrue($payload['isSplitPot']);
    }

    public function test_ignora_jogador_foldado_no_preview_de_showdown(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->where('seat_number', 3)
            ->update(['status' => 'folded']);

        $payload = (new PokerMultiSeatShowdownPreviewService())->preview($table->fresh(), [
            1 => ['name' => 'Par', 'rank' => 2, 'kickers' => [10]],
            2 => ['name' => 'Carta alta', 'rank' => 1, 'kickers' => [14]],
            3 => ['name' => 'Royal flush', 'rank' => 10, 'kickers' => [14]],
        ]);

        $this->assertSame(2, $payload['activeContenders']);
        $this->assertSame([1], $payload['winnerSeats']);
        $this->assertSame([1, 2], array_column($payload['ranking'], 'seatNumber'));
    }

    public function test_nao_resolve_showdown_sem_maos_avaliadas_para_todos_os_contendores(): void
    {
        $table = $this->tableWithSeats([1, 2, 3]);

        $payload = (new PokerMultiSeatShowdownPreviewService())->preview($table->fresh(), [
            1 => ['name' => 'Par', 'rank' => 2, 'kickers' => [10]],
        ]);

        $this->assertFalse($payload['canResolveShowdown']);
        $this->assertSame([], $payload['winnerSeats']);
        $this->assertNull($payload['ranking'][1]['handName']);
    }

    /**
     * @param array<int, int> $seats
     */
    private function tableWithSeats(array $seats): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa showdown 3+',
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
