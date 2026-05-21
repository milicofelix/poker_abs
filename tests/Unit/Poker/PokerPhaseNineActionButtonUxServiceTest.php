<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerPhaseNineActionButtonUxService;
use Tests\TestCase;

final class PokerPhaseNineActionButtonUxServiceTest extends TestCase
{
    public function test_fase_nove_dois_documenta_ux_dos_botoes_sem_alterar_regras(): void
    {
        $table = new PokerTable(['name' => 'Mesa teste', 'max_players' => 2]);

        $payload = (new PokerPhaseNineActionButtonUxService())->forTable($table, false);

        $this->assertSame('9.2', $payload['phase']);
        $this->assertSame('lobby', $payload['mode']);
        $this->assertFalse($payload['safeToChangeGameplay']);
        $this->assertCount(5, $payload['checklist']);
        $this->assertSame('primary-action', $payload['checklist'][0]['area']);
        $this->assertSame('locked-state', $payload['checklist'][1]['area']);
        $this->assertSame('raise-control', $payload['checklist'][2]['area']);
        $this->assertSame('regression-safety', $payload['checklist'][4]['area']);
        $this->assertSame('locked', $payload['checklist'][4]['status']);
    }

    public function test_mesa_local_recebe_contexto_visual_proprio_para_botoes(): void
    {
        $table = new PokerTable(['name' => 'Mesa local', 'max_players' => 2]);

        $payload = (new PokerPhaseNineActionButtonUxService())->forTable($table, true);

        $this->assertSame('local', $payload['mode']);
        $this->assertStringContainsString('mesa local', mb_strtolower($payload['summary']));
        $this->assertFalse($payload['safeToChangeGameplay']);
    }
}
