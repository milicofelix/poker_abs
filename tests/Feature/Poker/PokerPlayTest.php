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

    public function test_mesa_local_recebe_metadados_para_alinhar_com_lobby(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('Poker/Play', $page['component']);
        $this->assertArrayHasKey('table', $page['props']);
        $this->assertTrue($page['props']['table']['isLocalMode']);
        $this->assertSame('Mesa local', $page['props']['table']['name']);
        $this->assertSame(route('poker.actions'), $page['props']['table']['actionUrl']);
        $this->assertSame(route('poker.play', ['new' => 1]), $page['props']['table']['newHandUrl']);
        $this->assertSame(route('poker.lobby'), $page['props']['table']['lobbyUrl']);
    }
}
