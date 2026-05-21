<?php

namespace Tests\Unit\Poker;

use App\Services\Poker\PokerMultiSeatQaStabilityContractService;
use PHPUnit\Framework\TestCase;

final class PokerMultiSeatQaStabilityContractServiceTest extends TestCase
{
    public function test_qa_multi_seat_preserva_heads_up_sem_alertas(): void
    {
        $payload = (new PokerMultiSeatQaStabilityContractService())->forState([
            'multiSeat' => ['enabled' => false],
        ]);

        $this->assertSame('10.17', $payload['phase']);
        $this->assertSame('multi_seat_qa_stability.v0', $payload['schemaVersion']);
        $this->assertFalse($payload['enabled']);
        $this->assertSame('heads_up_preserved', $payload['status']);
        $this->assertSame([], $payload['criticalIssues']);
        $this->assertTrue($payload['regressionChecks']['headsUpPreserved']);
    }

    public function test_qa_multi_seat_aprova_estado_estavel_com_tres_jogadores(): void
    {
        $payload = (new PokerMultiSeatQaStabilityContractService())->forState([
            'isFinished' => false,
            'currentTurn' => ['seatNumber' => 2],
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    ['seatNumber' => 1, 'hasFolded' => false],
                    ['seatNumber' => 2, 'hasFolded' => false],
                    ['seatNumber' => 3, 'hasFolded' => false],
                ],
            ],
        ]);

        $this->assertTrue($payload['enabled']);
        $this->assertSame('stable', $payload['status']);
        $this->assertSame([], $payload['criticalIssues']);
        $this->assertSame([1, 2, 3], $payload['activeSeatNumbers']);
        $this->assertSame(3, $payload['activePlayerCount']);
        $this->assertSame(2, $payload['currentSeat']);
        $this->assertTrue($payload['regressionChecks']['currentTurnBelongsToActiveSeat']);
    }

    public function test_qa_multi_seat_sinaliza_turno_invalido_e_mao_finalizada_com_turno(): void
    {
        $payload = (new PokerMultiSeatQaStabilityContractService())->forState([
            'isFinished' => true,
            'currentTurn' => ['seatNumber' => 3],
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    ['seatNumber' => 1, 'hasFolded' => false],
                    ['seatNumber' => 2, 'hasFolded' => false],
                    ['seatNumber' => 3, 'hasFolded' => false],
                ],
            ],
        ]);

        $this->assertSame('attention_required', $payload['status']);
        $this->assertContains('mão finalizada ainda possui assento com turno ativo.', $payload['criticalIssues']);
        $this->assertFalse($payload['regressionChecks']['finishedHandHasNoTurn']);
        $this->assertNull($payload['currentSeat']);
    }
}
