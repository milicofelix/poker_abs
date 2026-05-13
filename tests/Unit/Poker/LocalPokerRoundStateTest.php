<?php

namespace Tests\Unit\Poker;

use App\Data\Poker\LocalPokerRoundState;
use App\Domain\Poker\Game\RoundStreet;
use PHPUnit\Framework\TestCase;

final class LocalPokerRoundStateTest extends TestCase
{
    public function test_consegue_criar_estado_da_rodada_com_valores_padrao(): void
    {
        $state = LocalPokerRoundState::fromArray([]);

        $this->assertSame(RoundStreet::PreFlop, $state->street);
        $this->assertSame(0, $state->pot);
        $this->assertSame(1000, $state->playerStack);
        $this->assertSame(20, $state->currentBet);
        $this->assertFalse($state->isFinished);
    }

    public function test_consegue_exportar_estado_da_rodada_para_array(): void
    {
        $state = new LocalPokerRoundState(
            street: RoundStreet::Turn,
            pot: 120,
            playerStack: 880,
            currentBet: 40,
            lastAction: ['type' => 'call'],
            isFinished: false,
        );

        $payload = $state->toArray();

        $this->assertSame('turn', $payload['street']);
        $this->assertSame('Turn', $payload['streetLabel']);
        $this->assertSame(120, $payload['pot']);
        $this->assertSame(880, $payload['playerStack']);
        $this->assertSame(40, $payload['currentBet']);
        $this->assertSame(['type' => 'call'], $payload['lastAction']);
    }
}
