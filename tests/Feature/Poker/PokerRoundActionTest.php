<?php

namespace Tests\Feature\Poker;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerRoundActionTest extends TestCase
{
    use RefreshDatabase;
    public function test_jogador_consegue_executar_uma_acao_e_receber_resposta_do_oponente(): void
    {
        $response = $this->postJson('/poker/actions', [
            'state' => [
                'street' => 'pre_flop',
                'pot' => 30,
                'playerStack' => 1000,
                'opponentStack' => 1000,
                'currentBet' => 20,
                'actionHistory' => [],
            ],
            'action' => 'call',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.pot', 70)
            ->assertJsonPath('state.playerStack', 980)
            ->assertJsonPath('state.opponentStack', 980)
            ->assertJsonPath('state.currentBet', 0)
            ->assertJsonPath('state.amountToCall', 0)
            ->assertJsonPath('state.actionHistory.0.actor', 'player')
            ->assertJsonPath('state.actionHistory.1.actor', 'opponent')
            ->assertJsonPath('state.conclusion.reason', 'in_progress');
    }

    public function test_quando_jogador_desiste_api_retorna_mao_finalizada(): void
    {
        $response = $this->postJson('/poker/actions', [
            'state' => [
                'street' => 'flop',
                'pot' => 70,
                'playerStack' => 980,
                'opponentStack' => 980,
                'currentBet' => 20,
                'actionHistory' => [],
            ],
            'action' => 'fold',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.isFinished', true)
            ->assertJsonPath('state.conclusion.reason', 'player_fold');
    }

    public function test_quando_mao_chega_ao_showdown_api_retorna_o_vencedor(): void
    {
        $response = $this->postJson('/poker/actions', [
            'state' => [
                'street' => 'river',
                'pot' => 120,
                'playerStack' => 940,
                'opponentStack' => 940,
                'currentBet' => 0,
                'actionHistory' => [],
                'bestHand' => [
                    'name' => 'Dois pares',
                    'rank' => 3,
                    'kickers' => [13, 8, 14],
                ],
                'opponentBestHand' => [
                    'name' => 'Par',
                    'rank' => 2,
                    'kickers' => [14, 13, 9, 4],
                ],
            ],
            'action' => 'check',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.isFinished', true)
            ->assertJsonPath('state.conclusion.reason', 'showdown')
            ->assertJsonPath('state.conclusion.winner.player', 'player')
            ->assertJsonPath('state.conclusion.winner.label', 'Você')
            ->assertJsonPath('state.conclusion.winner.handName', 'Dois pares');
    }

    public function test_api_rejeita_acao_de_poker_invalida(): void
    {
        $response = $this->postJson('/poker/actions', [
            'state' => [
                'street' => 'pre_flop',
                'pot' => 30,
                'playerStack' => 1000,
                'currentBet' => 20,
            ],
            'action' => 'invalid-action',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    }
}
