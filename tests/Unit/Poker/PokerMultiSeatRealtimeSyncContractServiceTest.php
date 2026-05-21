<?php

namespace Tests\Unit\Poker;

use App\Services\Poker\PokerMultiSeatRealtimeSyncContractService;
use PHPUnit\Framework\TestCase;

final class PokerMultiSeatRealtimeSyncContractServiceTest extends TestCase
{
    public function test_contrato_realtime_multi_seat_fica_inativo_no_heads_up(): void
    {
        $payload = (new PokerMultiSeatRealtimeSyncContractService())->forState([
            'multiSeat' => ['enabled' => false],
        ]);

        $this->assertSame('10.16', $payload['phase']);
        $this->assertSame('multi_seat_realtime_sync.v0', $payload['schemaVersion']);
        $this->assertFalse($payload['enabled']);
        $this->assertSame('heads_up_broadcast', $payload['mode']);
        $this->assertTrue($payload['requiresHydration']);
    }

    public function test_contrato_realtime_multi_seat_envia_sinal_leve_para_reidratacao_privada(): void
    {
        $payload = (new PokerMultiSeatRealtimeSyncContractService())->forState([
            'street' => 'turn',
            'isFinished' => false,
            'persistence' => [
                'tableId' => 10,
                'syncVersion' => 7,
            ],
            'multiSeat' => [
                'enabled' => true,
                'currentSeat' => 3,
                'players' => [
                    ['seatNumber' => 1, 'nickname' => 'Adriano', 'hasFolded' => false],
                    ['seatNumber' => 2, 'nickname' => 'Bot TAG', 'hasFolded' => true],
                    ['seatNumber' => 3, 'nickname' => 'Bot LAG', 'hasFolded' => false],
                ],
            ],
        ]);

        $this->assertTrue($payload['enabled']);
        $this->assertSame('multi_seat_lightweight_broadcast', $payload['mode']);
        $this->assertSame(10, $payload['tableId']);
        $this->assertSame(7, $payload['syncVersion']);
        $this->assertSame('turn', $payload['street']);
        $this->assertSame(3, $payload['currentSeat']);
        $this->assertSame([1, 3], $payload['activeSeatNumbers']);
        $this->assertSame(2, $payload['activePlayerCount']);
        $this->assertSame(3, $payload['totalPlayerCount']);
    }

    public function test_contrato_realtime_limpa_turno_quando_mao_termina(): void
    {
        $payload = (new PokerMultiSeatRealtimeSyncContractService())->forState([
            'isFinished' => true,
            'currentTurn' => ['seatNumber' => 2],
            'multiSeat' => [
                'enabled' => true,
                'currentSeat' => 2,
                'winnerSeats' => [1],
                'players' => [
                    ['seatNumber' => 1],
                    ['seatNumber' => 2],
                ],
            ],
        ]);

        $this->assertNull($payload['currentSeat']);
        $this->assertSame([1], $payload['winnerSeats']);
        $this->assertStringContainsString('finalizada', $payload['message']);
    }
}
