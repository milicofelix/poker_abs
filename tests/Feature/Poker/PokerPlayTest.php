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
        $this->assertSame('Mesa local', $page['props']['table']['modeLabel']);
        $this->assertSame(route('poker.actions'), $page['props']['table']['actionUrl']);
        $this->assertSame(route('poker.play', ['new' => 1]), $page['props']['table']['newHandUrl']);
        $this->assertSame(route('poker.lobby'), $page['props']['table']['lobbyUrl']);
        $this->assertCount(6, $page['props']['table']['reviewChecklist']);
        $this->assertSame('Fluxo de nova mão local', $page['props']['table']['reviewChecklist'][0]['label']);
        $this->assertSame('Showdown e hierarquia de mãos blindados', $page['props']['table']['reviewChecklist'][3]['label']);
        $this->assertSame('lobby', $page['props']['table']['reviewChecklist'][4]['status']);
    }

    public function test_mesa_local_recebe_auditoria_visual_da_fase_nove(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('9.1', $page['props']['table']['visualAudit']['phase']);
        $this->assertSame('local', $page['props']['table']['visualAudit']['mode']);
        $this->assertFalse($page['props']['table']['visualAudit']['safeToChangeGameplay']);
        $this->assertCount(5, $page['props']['table']['visualAudit']['checklist']);
        $this->assertSame('responsive', $page['props']['table']['visualAudit']['checklist'][3]['area']);
    }

}
