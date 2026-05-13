<?php

namespace Tests\Unit\Poker;

use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Domain\Poker\Game\SimpleOpponentStrategy;
use PHPUnit\Framework\TestCase;

final class SimpleOpponentStrategyTest extends TestCase
{
    public function test_oponente_paga_quando_existe_aposta_atual(): void
    {
        $decision = (new SimpleOpponentStrategy())->decide(
            street: RoundStreet::Flop,
            currentBet: 40,
            opponentStack: 1000,
        );

        $this->assertSame(PokerAction::Call, $decision->action);
        $this->assertSame(40, $decision->amount);
    }

    public function test_oponente_desiste_no_river_quando_aposta_esta_alta(): void
    {
        $decision = (new SimpleOpponentStrategy())->decide(
            street: RoundStreet::River,
            currentBet: 100,
            opponentStack: 1000,
        );

        $this->assertSame(PokerAction::Fold, $decision->action);
        $this->assertSame(0, $decision->amount);
    }
}
