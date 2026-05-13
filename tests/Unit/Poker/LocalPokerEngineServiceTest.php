<?php

namespace Tests\Unit\Poker;

use App\Services\Poker\LocalPokerEngineService;
use PHPUnit\Framework\TestCase;

final class LocalPokerEngineServiceTest extends TestCase
{
    public function test_engine_local_processa_call_e_retorna_estado_da_proxima_street(): void
    {
        $state = (new LocalPokerEngineService())->play([
            'street' => 'pre_flop',
            'pot' => 30,
            'playerStack' => 990,
            'opponentStack' => 980,
            'currentBet' => 20,
            'playerStreetBet' => 10,
            'opponentStreetBet' => 20,
            'actionHistory' => [],
        ], 'call');

        $this->assertSame('flop', $state['street']);
        $this->assertSame(40, $state['pot']);
        $this->assertSame(980, $state['playerStack']);
        $this->assertSame(980, $state['opponentStack']);
        $this->assertSame(10, $state['actionHistory'][0]['amount']);
        $this->assertSame(0, $state['actionHistory'][1]['amount']);
        $this->assertSame('in_progress', $state['conclusion']['reason']);
    }

    public function test_engine_local_rejeita_acao_invalida(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ação inválida para a rodada.');

        (new LocalPokerEngineService())->play([
            'street' => 'pre_flop',
            'pot' => 30,
            'playerStack' => 990,
            'currentBet' => 20,
        ], 'acao_inexistente');
    }
}
