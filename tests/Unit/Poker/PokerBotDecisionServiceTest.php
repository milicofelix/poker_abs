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

    public function test_bot_agressivo_pressona_pos_flop_com_draw_e_range_forte(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'aggressive',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 0,
            currentBet: 40,
            botStack: 960,
            handStrength: [
                'score' => 54,
                'label' => 'flush draw',
                'range' => 'strong',
                'drawBonus' => 12,
                'pressureBonus' => 5,
            ],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertGreaterThan(40, $decision->amount);
    }

    public function test_bot_conservador_desvaloriza_range_fraco_pre_flop(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'conservative',
            difficulty: 'normal',
            street: RoundStreet::PreFlop,
            amountToCall: 90,
            currentBet: 90,
            botStack: 1000,
            handStrength: [
                'score' => 48,
                'label' => 'mão fraca',
                'range' => 'trash',
            ],
        );

        $this->assertSame(PokerAction::Fold, $decision->action);
    }


    public function test_bot_tag_aumenta_com_mao_forte_e_range_protegido(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'tag',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 20,
            currentBet: 40,
            botStack: 960,
            handStrength: ['score' => 70, 'label' => 'top pair', 'range' => 'strong'],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertStringContainsString('TAG', $decision->message);
    }

    public function test_bot_nit_larga_mao_media_contra_pressao(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'nit',
            difficulty: 'normal',
            street: RoundStreet::Turn,
            amountToCall: 90,
            currentBet: 90,
            botStack: 1000,
            handStrength: ['score' => 58, 'label' => 'mão média'],
        );

        $this->assertSame(PokerAction::Fold, $decision->action);
        $this->assertStringContainsString('Nit', $decision->message);
    }

    public function test_bot_calling_station_paga_mao_fraca_que_outros_largariam(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'calling_station',
            difficulty: 'normal',
            street: RoundStreet::River,
            amountToCall: 80,
            currentBet: 80,
            botStack: 1000,
            handStrength: ['score' => 34, 'label' => 'terceiro par'],
        );

        $this->assertSame(PokerAction::Call, $decision->action);
        $this->assertSame(80, $decision->amount);
        $this->assertStringContainsString('Calling Station', $decision->message);
    }

    public function test_bot_maniac_pressona_com_mao_marginal_pos_flop(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'maniac',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 0,
            currentBet: 40,
            botStack: 960,
            handStrength: ['score' => 36, 'label' => 'mão marginal'],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertStringContainsString('Maniac', $decision->message);
    }

    public function test_contexto_de_pote_grande_e_stack_curto_faz_bot_agressivo_pressionar(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'aggressive',
            difficulty: 'normal',
            street: RoundStreet::Turn,
            amountToCall: 0,
            currentBet: 80,
            botStack: 150,
            handStrength: [
                'score' => 50,
                'label' => 'mão média',
                'boardTexture' => 'dry',
                'context' => [
                    'potPressure' => 'large_pot',
                    'stackPressure' => 'short_stack',
                    'spr' => 0.58,
                    'opponentAggressionRate' => 25,
                    'opponentFoldRate' => 0,
                ],
            ],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertStringContainsString('pote grande', $decision->message);
        $this->assertStringContainsString('stack curto', $decision->message);
    }

    public function test_contexto_de_spr_alto_e_board_perigoso_faz_conservador_largar_mao_marginal(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'conservative',
            difficulty: 'normal',
            street: RoundStreet::River,
            amountToCall: 90,
            currentBet: 90,
            botStack: 1000,
            handStrength: [
                'score' => 48,
                'label' => 'mão marginal',
                'boardTexture' => 'dangerous',
                'context' => [
                    'potPressure' => 'normal_pot',
                    'stackPressure' => 'deep_stack',
                    'spr' => 10,
                    'opponentAggressionRate' => 75,
                    'opponentFoldRate' => 0,
                ],
            ],
        );

        $this->assertSame(PokerAction::Fold, $decision->action);
        $this->assertStringContainsString('vilão agressivo', $decision->message);
    }

    public function test_bot_agressivo_faz_semi_bluff_com_combo_draw_e_ev_favoravel(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'aggressive',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 0,
            currentBet: 60,
            botStack: 900,
            handStrength: [
                'score' => 44,
                'label' => 'combo draw',
                'range' => 'strong',
                'drawBonus' => 22,
                'pressureBonus' => 5,
                'probability' => [
                    'outs' => 15,
                    'equity' => 60,
                    'potOdds' => 0,
                    'evScore' => 60,
                    'callRecommendation' => 'value_bet',
                    'outsLabel' => 'combo draw com 15 outs aproximados',
                ],
            ],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertStringContainsString('probabilidade', $decision->message);
    }

    public function test_bot_conservador_larga_mao_fraca_quando_pot_odds_sao_ruins(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'conservative',
            difficulty: 'normal',
            street: RoundStreet::Turn,
            amountToCall: 140,
            currentBet: 140,
            botStack: 1000,
            handStrength: [
                'score' => 42,
                'label' => 'mão fraca',
                'range' => 'trash',
                'probability' => [
                    'outs' => 0,
                    'equity' => 18,
                    'potOdds' => 64,
                    'evScore' => -46,
                    'callRecommendation' => 'fold_by_odds',
                ],
            ],
        );

        $this->assertSame(PokerAction::Fold, $decision->action);
        $this->assertStringContainsString('odds ruins', $decision->message);
    }


    public function test_memoria_de_adversario_que_folda_demais_faz_lag_pressionar_spot_marginal(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'lag',
            difficulty: 'normal',
            street: RoundStreet::Flop,
            amountToCall: 0,
            currentBet: 40,
            botStack: 960,
            handStrength: [
                'score' => 39,
                'label' => 'mão marginal',
                'context' => [
                    'memory' => [
                        'opponentModel' => 'overfolder',
                        'opponentModelLabel' => 'adversário folda demais',
                        'scoreAdjustment' => 7,
                        'bluffPressure' => 10,
                        'callDownBias' => 0,
                    ],
                ],
            ],
        );

        $this->assertSame(PokerAction::Raise, $decision->action);
        $this->assertStringContainsString('memória: adversário folda demais', $decision->message);
    }

    public function test_memoria_de_adversario_agressivo_aumenta_tolerancia_para_pagar(): void
    {
        $decision = (new PokerBotDecisionService())->decide(
            profile: 'conservative',
            difficulty: 'normal',
            street: RoundStreet::Turn,
            amountToCall: 90,
            currentBet: 90,
            botStack: 1000,
            handStrength: [
                'score' => 45,
                'label' => 'mão marginal',
                'context' => [
                    'memory' => [
                        'opponentModel' => 'aggressive_opponent',
                        'opponentModelLabel' => 'adversário muito agressivo',
                        'scoreAdjustment' => 4,
                        'bluffPressure' => 0,
                        'callDownBias' => 10,
                    ],
                ],
            ],
        );

        $this->assertSame(PokerAction::Call, $decision->action);
        $this->assertStringContainsString('memória: adversário muito agressivo', $decision->message);
    }

}
