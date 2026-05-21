<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerPhaseNineVisualAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPhaseNineVisualAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_visual_da_fase_nove_nao_libera_mudanca_de_gameplay(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa visual',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = (new PokerPhaseNineVisualAuditService())->forTable($table, false);

        $this->assertSame('9.1', $payload['phase']);
        $this->assertSame('lobby', $payload['mode']);
        $this->assertFalse($payload['safeToChangeGameplay']);
        $this->assertCount(5, $payload['checklist']);
        $this->assertSame('layout', $payload['checklist'][0]['area']);
        $this->assertSame('protected', $payload['checklist'][4]['status']);
        $this->assertContains('Não liberar mesa 3+ jogadores nesta fase visual.', $payload['guardrails']);
    }

    public function test_auditoria_visual_mantem_modo_local_separado_do_lobby(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa local visual',
            'status' => 'active',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = (new PokerPhaseNineVisualAuditService())->forTable($table, true);

        $this->assertSame('local', $payload['mode']);
        $this->assertSame('lobby-only', $payload['checklist'][4]['status']);
        $this->assertStringContainsString('Modo local auditado', $payload['summary']);
    }
}
