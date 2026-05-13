<?php

namespace Tests\Unit\Poker;

use App\Application\Poker\StartPokerHandAction;
use App\Domain\Poker\Hands\HandEvaluator;
use PHPUnit\Framework\TestCase;

final class StartPokerHandActionTest extends TestCase
{
    public function test_nova_mao_ja_comeca_com_blinds_e_aposta_pendente_corretos(): void
    {
        $state = (new StartPokerHandAction(new HandEvaluator()))->execute();

        $this->assertSame('pre_flop', $state['street']);
        $this->assertSame(30, $state['pot']);
        $this->assertSame(990, $state['playerStack']);
        $this->assertSame(980, $state['opponentStack']);
        $this->assertSame(20, $state['currentBet']);
        $this->assertSame(10, $state['playerStreetBet']);
        $this->assertSame(20, $state['opponentStreetBet']);
        $this->assertSame(10, $state['amountToCall']);
        $this->assertSame(20, $state['minimumRaise']);
        $this->assertSame(40, $state['minimumRaiseTo']);
        $this->assertFalse($state['canCheck']);
        $this->assertTrue($state['canCall']);
        $this->assertTrue($state['canRaise']);
    }
}
