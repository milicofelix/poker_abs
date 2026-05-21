<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerPhaseThirteenDesignAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPhaseThirteenDesignAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_visual_da_fase_treze_mapeia_design_sem_alterar_gameplay(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa premium em auditoria',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $payload = (new PokerPhaseThirteenDesignAuditService())->forTable($table, false);

        $this->assertSame('13.1', $payload['phase']);
        $this->assertSame('multiplayer', $payload['mode']);
        $this->assertFalse($payload['safeToChangeGameplay']);
        $this->assertSame('Mesa premium em auditoria', $payload['tableContext']['name']);
        $this->assertCount(9, $payload['checklist']);
        $this->assertSame('buttons', $payload['checklist'][0]['area']);
        $this->assertSame('protected', $payload['checklist'][4]['status']);
        $this->assertSame('realtime-safe', $payload['checklist'][8]['status']);
        $this->assertArrayHasKey('buttons', $payload['designTokens']);
        $this->assertSame('PokerSurface', $payload['foundation'][0]['component']);
        $this->assertSame('ready', $payload['foundation'][3]['status']);
        $this->assertContains('PokerPageShell', array_column($payload['foundation'], 'component'));
        $this->assertContains('PokerFlashMessage', array_column($payload['foundation'], 'component'));
        $this->assertContains('PokerResponsiveTable', array_column($payload['foundation'], 'component'));
        $this->assertSame('pilot-ready', $payload['checklist'][2]['status']);
        $this->assertSame('gradient + max-width + responsive padding', $payload['designTokens']['surface']['pageShell']);
        $this->assertContains('Não alterar cálculo de vencedor, apostas, blinds, side pots, bankroll, timer ou timeout automático.', $payload['guardrails']);
    }

    public function test_auditoria_visual_da_fase_treze_mantem_modo_local_isolado_do_multiplayer(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa local premium',
            'status' => 'active',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = (new PokerPhaseThirteenDesignAuditService())->forTable($table, true);

        $this->assertSame('local', $payload['mode']);
        $this->assertSame('local-safe', $payload['checklist'][8]['status']);
        $this->assertStringContainsString('Modo local preparado', $payload['summary']);
        $this->assertContains('13.1.3 — Aplicar shell, hero, cards métricos e tabela responsiva no Ranking financeiro.', $payload['nextSteps']);
        $this->assertContains('13.1.4 — Aplicar shell, cards, tabela responsiva e estados vazios em Bankroll.', $payload['nextSteps']);
        $this->assertContains('13.2 — Iniciar mesa premium com cartas, fichas, avatares, ação atual, animações e mobile.', $payload['nextSteps']);
    }
}
