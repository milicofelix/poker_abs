<?php

namespace Tests\Unit\Poker;

use App\Services\Poker\PokerBotHandStrengthService;
use PHPUnit\Framework\TestCase;

final class PokerBotHandStrengthServiceTest extends TestCase
{
    public function test_par_alto_pre_flop_gera_forca_alta_para_o_bot(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'pre_flop',
            'opponentCards' => [
                ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                ['rank' => 'A', 'suit' => 'hearts', 'label' => 'A♥'],
            ],
            'opponentBestHand' => ['rank' => 2],
        ], 'opponent');

        $this->assertGreaterThanOrEqual(75, $strength['score']);
        $this->assertSame('mão forte', $strength['label']);
        $this->assertTrue($strength['hasPairInHand']);
    }

    public function test_cartas_baixas_desconectadas_pre_flop_geram_forca_fraca(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'pre_flop',
            'opponentCards' => [
                ['rank' => '2', 'suit' => 'spades', 'label' => '2♠'],
                ['rank' => '7', 'suit' => 'hearts', 'label' => '7♥'],
            ],
            'opponentBestHand' => ['rank' => 1],
        ], 'opponent');

        $this->assertLessThan(50, $strength['score']);
        $this->assertSame('mão fraca', $strength['label']);
        $this->assertFalse($strength['hasPairInHand']);
    }
}
