<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatDealPreviewService;
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
        $this->assertSame('10.4', $payload['multiSeat']['turnCyclePreview']['phase']);
        $this->assertSame('multi_seat_turn_cycle.v0', $payload['multiSeat']['turnCyclePreview']['schemaVersion']);
        $this->assertSame('multi_seat_actions.v0', $payload['multiSeat']['actionPreview']['schemaVersion']);
        $this->assertSame('multi_seat_betting_round.v0', $payload['multiSeat']['bettingRoundPreview']['schemaVersion']);
        $this->assertSame('10.6', $payload['multiSeat']['bettingRoundPreview']['phase']);
        $this->assertSame('multi_seat_showdown.v0', $payload['multiSeat']['showdownPreview']['schemaVersion']);
        $this->assertSame('10.7', $payload['multiSeat']['showdownPreview']['phase']);
        $this->assertFalse($payload['multiSeat']['actionPreview']['enabledInMainEngine']);
        $this->assertFalse($payload['multiSeat']['bettingRoundPreview']['enabledInMainEngine']);
        $this->assertFalse($payload['multiSeat']['showdownPreview']['enabledInMainEngine']);
        $this->assertFalse($payload['multiSeat']['turnCyclePreview']['enabledInMainEngine']);
        $this->assertSame(1, $payload['multiSeat']['turnCyclePreview']['dealerSeat']);
        $this->assertSame(3, $payload['multiSeat']['turnCyclePreview']['smallBlindSeat']);
        $this->assertSame(5, $payload['multiSeat']['turnCyclePreview']['bigBlindSeat']);
        $this->assertSame(1, $payload['multiSeat']['turnCyclePreview']['firstPreFlopSeat']);
        $this->assertTrue($payload['compatibility']['headsUpStillAuthoritative']);
    }

    private function service(): PokerTableStateContractService
    {
        $preparation = new PokerMultiSeatEnginePreparationService();

        return new PokerTableStateContractService(
            $preparation,
            new PokerMultiSeatDealPreviewService($preparation),
        );
    }
}
