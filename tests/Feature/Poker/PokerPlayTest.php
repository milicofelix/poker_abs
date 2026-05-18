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


    public function test_mesa_local_recebe_melhorias_visuais_dos_botoes_de_acao(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('9.2', $page['props']['table']['actionButtonUx']['phase']);
        $this->assertSame('local', $page['props']['table']['actionButtonUx']['mode']);
        $this->assertFalse($page['props']['table']['actionButtonUx']['safeToChangeGameplay']);
        $this->assertCount(5, $page['props']['table']['actionButtonUx']['checklist']);
        $this->assertSame('raise-control', $page['props']['table']['actionButtonUx']['checklist'][2]['area']);
    }


    public function test_mesa_local_recebe_feedback_visual_de_turno_showdown_e_vencedor(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('9.3', $page['props']['table']['stateFeedback']['phase']);
        $this->assertSame('local', $page['props']['table']['stateFeedback']['mode']);
        $this->assertFalse($page['props']['table']['stateFeedback']['safeToChangeGameplay']);
        $this->assertCount(5, $page['props']['table']['stateFeedback']['checklist']);
        $this->assertSame('showdown-state', $page['props']['table']['stateFeedback']['checklist'][2]['area']);
        $this->assertSame('winner-feedback', $page['props']['table']['stateFeedback']['checklist'][3]['area']);
    }


    public function test_mesa_local_recebe_metadados_das_animacoes_leves(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('9.4', $page['props']['table']['motionUx']['phase']);
        $this->assertSame('local', $page['props']['table']['motionUx']['mode']);
        $this->assertFalse($page['props']['table']['motionUx']['safeToChangeGameplay']);
        $this->assertCount(5, $page['props']['table']['motionUx']['checklist']);
        $this->assertSame('card-motion', $page['props']['table']['motionUx']['checklist'][0]['area']);
        $this->assertSame('accessibility', $page['props']['table']['motionUx']['checklist'][4]['area']);
    }


    public function test_mesa_local_recebe_metadados_de_responsividade_mobile(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('9.5', $page['props']['table']['responsiveUx']['phase']);
        $this->assertSame('local', $page['props']['table']['responsiveUx']['mode']);
        $this->assertFalse($page['props']['table']['responsiveUx']['safeToChangeGameplay']);
        $this->assertCount(5, $page['props']['table']['responsiveUx']['checklist']);
        $this->assertSame('safe-area', $page['props']['table']['responsiveUx']['checklist'][0]['area']);
        $this->assertSame('mobile-actions', $page['props']['table']['responsiveUx']['checklist'][3]['area']);
    }


    public function test_mesa_local_recebe_metadados_do_polimento_final_da_fase_nove(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('9.6', $page['props']['table']['finalPolish']['phase']);
        $this->assertSame('local', $page['props']['table']['finalPolish']['mode']);
        $this->assertFalse($page['props']['table']['finalPolish']['safeToChangeGameplay']);
        $this->assertSame('10', $page['props']['table']['finalPolish']['nextPhase']['phase']);
        $this->assertCount(5, $page['props']['table']['finalPolish']['checklist']);
        $this->assertSame('terminal-state-guard', $page['props']['table']['finalPolish']['checklist'][2]['area']);
        $this->assertSame('phase-nine-closure', $page['props']['table']['finalPolish']['checklist'][4]['area']);
    }

}
