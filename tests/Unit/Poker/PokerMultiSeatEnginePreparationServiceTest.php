<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatEnginePreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatEnginePreparationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordem_de_turno_respeita_assentos_a_partir_do_dealer(): void
    {
        $table = $this->tableWithPlayers(6, [
            ['id' => 10, 'nickname' => 'Adriano', 'seat_number' => 1, 'is_bot' => false],
            ['id' => 11, 'nickname' => 'Maria', 'seat_number' => 3, 'is_bot' => false],
            ['id' => 12, 'nickname' => 'Bot João', 'seat_number' => 5, 'is_bot' => true],
        ]);

        $payload = (new PokerMultiSeatEnginePreparationService())->preparationPayload($table, dealerSeat: 3);

        $this->assertSame([3, 5, 1], array_column($payload['turnOrder'], 'seatNumber'));
        $this->assertSame(3, $payload['blinds']['dealerSeat']);
        $this->assertSame(5, $payload['blinds']['smallBlindSeat']);
        $this->assertSame(1, $payload['blinds']['bigBlindSeat']);
        $this->assertFalse($payload['isMultiSeatEngineEnabled']);
    }

    public function test_heads_up_atual_preserva_dealer_como_small_blind(): void
    {
        $table = $this->tableWithPlayers(2, [
            ['id' => 10, 'nickname' => 'Adriano', 'seat_number' => 1, 'is_bot' => false],
            ['id' => 11, 'nickname' => 'Bot Maria', 'seat_number' => 2, 'is_bot' => true],
        ]);

        $payload = (new PokerMultiSeatEnginePreparationService())->preparationPayload($table, dealerSeat: 1);

        $this->assertSame('heads_up', $payload['engineMode']);
        $this->assertSame([1, 2], array_column($payload['turnOrder'], 'seatNumber'));
        $this->assertSame(1, $payload['blinds']['smallBlindSeat']);
        $this->assertSame(2, $payload['blinds']['bigBlindSeat']);
        $this->assertFalse($payload['blinds']['isReadyForMultiSeatBlinds']);
    }

    /**
     * @param array<int, array{id:int, nickname:string, seat_number:int, is_bot:bool}> $players
     */
    private function tableWithPlayers(int $maxPlayers, array $players): PokerTable
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa teste multi-seat',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => $maxPlayers,
        ]);

        foreach ($players as $playerData) {
            $user = User::query()->create([
                'name' => $playerData['nickname'],
                'email' => sprintf('multi-seat-%d@example.test', $playerData['id']),
                'password' => 'password',
            ]);

            PokerTablePlayer::query()->create([
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
                'nickname' => $playerData['nickname'],
                'seat_number' => $playerData['seat_number'],
                'is_bot' => $playerData['is_bot'],
                'stack' => 1000,
                'status' => 'online',
                'joined_at' => now(),
            ]);
        }

        return $table->refresh();
    }
}
