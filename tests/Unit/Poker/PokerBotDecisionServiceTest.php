<?php

namespace Tests\Unit\Poker;

use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Services\Poker\PokerBotDecisionService;
use PHPUnit\Framework\TestCase;

final class PokerBotDecisionServiceTest extends TestCase
{
    public function test_bot_conservador_desiste_quando_aposta_exige_risco_alto(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'conservative',
            difficulty: 'easy',
            street: RoundStreet::Turn,
            amountToCall: 120,
            currentBet: 120,
            botStack: 1000,
        );

        $this->assertSame(PokerAction::Fold, $decision->action);
    }

    public function test_bot_agressivo_paga_aposta_controlada_para_continuar_na_mao(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'aggressive',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 80,
            currentBet: 80,
            botStack: 1000,
        );

        $this->assertSame(PokerAction::Call, $decision->action);
        $this->assertSame(80, $decision->amount);
    }
    public function test_bot_agressivo_aumenta_quando_a_forca_da_mao_e_alta(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'aggressive',
            difficulty: 'normal',
            street: RoundStreet::PreFlop,
            amountToCall: 10,
            currentBet: 20,
            botStack: 980,
            handStrength: ['score' => 88, 'label' => 'mão forte'],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertGreaterThan(20, $decision->amount);
    }

    public function test_bot_conservador_paga_com_mao_jogavel_mesmo_com_aposta_moderada(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'conservative',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 70,
            currentBet: 70,
            botStack: 1000,
            handStrength: ['score' => 62, 'label' => 'mão jogável'],
        );

        $this->assertSame(PokerAction::Call, $decision->action);
        $this->assertSame(70, $decision->amount);
    }

    public function test_dificuldade_facil_e_dificil_podem_tomar_decisoes_diferentes_com_a_mesma_mao(): void
    {
        $service = new PokerBotDecisionService();

        $facil = $service->decide(
            profile: 'conservative',
            difficulty: 'easy',
            street: RoundStreet::Flop,
            amountToCall: 90,
            currentBet: 90,
            botStack: 1000,
            handStrength: ['score' => 50, 'label' => 'mão marginal'],
        );

        $dificil = $service->decide(
            profile: 'conservative',
            difficulty: 'hard',
            street: RoundStreet::Flop,
            amountToCall: 90,
            currentBet: 90,
            botStack: 1000,
            handStrength: ['score' => 50, 'label' => 'mão marginal'],
        );

        $this->assertSame(PokerAction::Fold, $facil->action);
        $this->assertSame(PokerAction::Call, $dificil->action);
        $this->assertStringContainsString('Fácil', $facil->message);
        $this->assertStringContainsString('Difícil', $dificil->message);
    }

    public function test_bot_agressivo_dificil_pressona_com_mao_media_enquanto_facil_controla_o_pote(): void
    {
        $service = new PokerBotDecisionService();

        $facil = $service->decide(
            profile: 'aggressive',
            difficulty: 'easy',
            street: RoundStreet::Turn,
            amountToCall: 0,
            currentBet: 40,
            botStack: 960,
            handStrength: ['score' => 60, 'label' => 'mão média'],
        );

        $dificil = $service->decide(
            profile: 'aggressive',
            difficulty: 'hard',
            street: RoundStreet::Turn,
            amountToCall: 0,
            currentBet: 40,
            botStack: 960,
            handStrength: ['score' => 60, 'label' => 'mão média'],
        );

        $this->assertSame(PokerAction::Check, $facil->action);
        $this->assertSame(PokerAction::Raise, $dificil->action);
    }

}
