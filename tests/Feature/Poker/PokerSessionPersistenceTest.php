<?php

namespace Tests\Feature\Poker;

use Tests\TestCase;

final class PokerSessionPersistenceTest extends TestCase
{
    public function test_pagina_do_poker_guarda_a_mao_inicial_na_sessao(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();
        $this->assertIsArray(session('poker.local_hand'));
        $this->assertArrayHasKey('playerCards', session('poker.local_hand'));
        $this->assertArrayHasKey('communityCards', session('poker.local_hand'));
    }

    public function test_pagina_do_poker_reaproveita_mao_em_andamento_da_sessao(): void
    {
        $state = [
            'street' => 'turn',
            'streetLabel' => 'Turn',
            'pot' => 90,
            'playerStack' => 950,
            'opponentStack' => 960,
            'currentBet' => 0,
            'playerCards' => [],
            'opponentCards' => [],
            'communityCards' => [],
            'bestHand' => ['name' => 'Par', 'rank' => 2, 'kickers' => [14]],
            'opponentBestHand' => ['name' => 'Carta alta', 'rank' => 1, 'kickers' => [13]],
        ];

        $response = $this->withSession([
            'poker.local_hand' => $state,
        ])->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('Poker/Play', $page['component']);
        $this->assertSame('turn', $page['props']['hand']['street']);
        $this->assertSame(90, $page['props']['hand']['pot']);
    }

    public function test_acao_do_poker_atualiza_estado_da_mao_na_sessao(): void
    {
        $response = $this->withSession([
            'poker.local_hand' => [
                'street' => 'pre_flop',
                'pot' => 30,
                'playerStack' => 1000,
                'opponentStack' => 1000,
                'currentBet' => 20,
                'actionHistory' => [],
            ],
        ])->postJson('/poker/actions', [
            'action' => 'call',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.street', 'flop');

        $this->assertSame('flop', session('poker.local_hand.street'));
        $this->assertSame(70, session('poker.local_hand.pot'));
    }

    public function test_parametro_new_descarta_mao_anterior_e_cria_uma_nova_mao(): void
    {
        $response = $this->withSession([
            'poker.local_hand' => [
                'street' => 'river',
                'pot' => 999,
            ],
        ])->get('/poker?new=1');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertArrayHasKey('playerCards', $page['props']['hand']);
        $this->assertNotSame(999, $page['props']['hand']['pot'] ?? null);
        $this->assertIsArray(session('poker.local_hand'));
    }
}
