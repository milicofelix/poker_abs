<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatEnginePreparationService;
use App\Services\Poker\PokerTableStateContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerTableStateContractServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_contrato_heads_up_continua_sendo_a_fonte_oficial(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa heads-up',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = $this->service()->forTable($table, [
            'street' => 'pre_flop',
            'pot' => 30,
            'currentTurn' => ['actor' => 'player'],
            'isFinished' => false,
        ]);

        $this->assertSame('heads_up', $payload['active']);
        $this->assertNull($payload['next']);
        $this->assertTrue($payload['headsUp']['isCurrentEngine']);
        $this->assertSame(['player', 'opponent'], $payload['headsUp']['canonicalActors']);
        $this->assertSame([1, 2], $payload['headsUp']['supportedSeats']);
        $this->assertFalse($payload['multiSeat']['isEnabled']);
        $this->assertFalse($payload['compatibility']['canStartThreePlusPlayers']);
    }

    public function test_contrato_multi_seat_fica_separado_e_desativado_para_mesa_3_mais(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat futura',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        foreach ([1 => 'Adriano', 3 => 'Maria', 5 => 'Bot João'] as $seat => $nickname) {
            $user = User::query()->create([
                'name' => $nickname,
                'email' => sprintf('contract-seat-%d@example.test', $seat),
                'password' => 'password',
            ]);

            PokerTablePlayer::query()->create([
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
                'nickname' => $nickname,
                'seat_number' => $seat,
                'is_bot' => str_contains($nickname, 'Bot'),
                'stack' => 1000,
                'status' => 'online',
                'joined_at' => now(),
            ]);
        }

        $payload = $this->service()->forTable($table->refresh(), [
            'street' => 'pre_flop',
            'pot' => 30,
            'isFinished' => false,
        ]);

        $this->assertSame('heads_up', $payload['active']);
        $this->assertSame('multi_seat', $payload['next']);
        $this->assertSame('multi_seat.v0', $payload['multiSeat']['schemaVersion']);
        $this->assertFalse($payload['multiSeat']['isCurrentEngine']);
        $this->assertFalse($payload['multiSeat']['isEnabled']);
        $this->assertSame(6, $payload['multiSeat']['declaredMaxPlayers']);
        $this->assertSame([1, 2, 3, 4, 5, 6], $payload['multiSeat']['supportedSeats']);
        $this->assertSame([1, 3, 5], array_column($payload['multiSeat']['turnOrder'], 'seatNumber'));
        $this->assertTrue($payload['compatibility']['headsUpStillAuthoritative']);
    }

    private function service(): PokerTableStateContractService
    {
        return new PokerTableStateContractService(new PokerMultiSeatEnginePreparationService());
    }
}
