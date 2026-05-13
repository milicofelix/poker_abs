<?php

namespace Tests\Feature\Poker;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPlayTest extends TestCase
{
    use RefreshDatabase;
    public function test_jogador_consegue_abrir_a_pagina_do_poker(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();
    }

    public function test_pagina_do_poker_recebe_as_cartas_iniciais_da_mao(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('Poker/Play', $page['component']);
        $this->assertArrayHasKey('hand', $page['props']);
        $this->assertArrayHasKey('playerCards', $page['props']['hand']);
        $this->assertArrayHasKey('communityCards', $page['props']['hand']);
        $this->assertArrayHasKey('bestHand', $page['props']['hand']);
    }
}
