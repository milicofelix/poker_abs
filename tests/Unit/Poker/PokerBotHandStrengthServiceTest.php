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
    public function test_range_premium_pre_flop_e_identificado_para_cartas_fortes(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'pre_flop',
            'opponentCards' => [
                ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                ['rank' => 'K', 'suit' => 'spades', 'label' => 'K♠'],
            ],
            'opponentBestHand' => ['rank' => 1],
        ], 'opponent');

        $this->assertSame('premium', $strength['range']);
        $this->assertSame('range premium', $strength['rangeLabel']);
        $this->assertGreaterThanOrEqual(75, $strength['score']);
    }

    public function test_pos_flop_identifica_flush_draw_e_board_perigoso(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'flop',
            'opponentCards' => [
                ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                ['rank' => 'Q', 'suit' => 'spades', 'label' => 'Q♠'],
            ],
            'communityCards' => [
                ['rank' => '2', 'suit' => 'spades', 'label' => '2♠'],
                ['rank' => '8', 'suit' => 'spades', 'label' => '8♠'],
                ['rank' => 'J', 'suit' => 'hearts', 'label' => 'J♥'],
            ],
            'opponentBestHand' => ['rank' => 1],
        ], 'opponent');

        $this->assertTrue($strength['hasFlushDraw']);
        $this->assertSame('flush draw', $strength['label']);
        $this->assertSame('dangerous', $strength['boardTexture']);
        $this->assertGreaterThanOrEqual(12, $strength['drawBonus']);
    }

    public function test_pos_flop_identifica_straight_draw(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'turn',
            'opponentCards' => [
                ['rank' => '9', 'suit' => 'clubs', 'label' => '9♣'],
                ['rank' => '10', 'suit' => 'diamonds', 'label' => '10♦'],
            ],
            'communityCards' => [
                ['rank' => 'J', 'suit' => 'spades', 'label' => 'J♠'],
                ['rank' => 'Q', 'suit' => 'hearts', 'label' => 'Q♥'],
                ['rank' => '3', 'suit' => 'clubs', 'label' => '3♣'],
                ['rank' => '6', 'suit' => 'diamonds', 'label' => '6♦'],
            ],
            'opponentBestHand' => ['rank' => 1],
        ], 'opponent');

        $this->assertTrue($strength['hasStraightDraw']);
        $this->assertSame('straight draw', $strength['label']);
        $this->assertGreaterThanOrEqual(10, $strength['drawBonus']);
    }

    public function test_simulacao_probabilistica_calcula_outs_equidade_e_pot_odds(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'flop',
            'pot' => 120,
            'amountToCall' => 40,
            'opponentCards' => [
                ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                ['rank' => 'K', 'suit' => 'spades', 'label' => 'K♠'],
            ],
            'communityCards' => [
                ['rank' => 'Q', 'suit' => 'spades', 'label' => 'Q♠'],
                ['rank' => 'J', 'suit' => 'spades', 'label' => 'J♠'],
                ['rank' => '2', 'suit' => 'clubs', 'label' => '2♣'],
            ],
            'opponentBestHand' => ['rank' => 1],
        ], 'opponent');

        $this->assertSame('combo draw', $strength['label']);
        $this->assertTrue($strength['probability']['hasComboDraw']);
        $this->assertGreaterThanOrEqual(15, $strength['outs']);
        $this->assertGreaterThanOrEqual(60, $strength['equity']);
        $this->assertSame(25, $strength['potOdds']);
        $this->assertSame('profitable_call_or_raise', $strength['probability']['callRecommendation']);
    }

    public function test_simulacao_probabilistica_desvaloriza_call_com_odds_ruins(): void
    {
        $strength = (new PokerBotHandStrengthService())->evaluate([
            'street' => 'turn',
            'pot' => 80,
            'amountToCall' => 140,
            'opponentCards' => [
                ['rank' => '7', 'suit' => 'clubs', 'label' => '7♣'],
                ['rank' => '2', 'suit' => 'diamonds', 'label' => '2♦'],
            ],
            'communityCards' => [
                ['rank' => 'A', 'suit' => 'hearts', 'label' => 'A♥'],
                ['rank' => 'K', 'suit' => 'spades', 'label' => 'K♠'],
                ['rank' => '9', 'suit' => 'clubs', 'label' => '9♣'],
                ['rank' => '4', 'suit' => 'diamonds', 'label' => '4♦'],
            ],
            'opponentBestHand' => ['rank' => 1],
        ], 'opponent');

        $this->assertSame(0, $strength['outs']);
        $this->assertGreaterThan(60, $strength['potOdds']);
        $this->assertSame('fold_by_odds', $strength['probability']['callRecommendation']);
        $this->assertLessThan(35, $strength['score']);
    }

}
