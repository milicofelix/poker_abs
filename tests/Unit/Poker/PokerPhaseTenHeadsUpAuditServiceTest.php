<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerPhaseTenHeadsUpAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPhaseTenHeadsUpAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_da_fase_10_mantem_tres_mais_bloqueado(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa futura 3+',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $payload = (new PokerPhaseTenHeadsUpAuditService())->forTable($table, false);

        $this->assertSame('10.1', $payload['phase']);
        $this->assertSame('heads_up', $payload['activeEngine']);
        $this->assertSame('multi_seat', $payload['targetEngine']);
        $this->assertFalse($payload['threePlusEnabled']);
        $this->assertTrue($payload['isMultiSeatCandidate']);
        $this->assertSame(6, $payload['declaredMaxPlayers']);
        $this->assertSame(2, $payload['currentEngineMaxPlayers']);
    }

    public function test_auditoria_lista_travas_antes_do_motor_multi_jogador(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa heads-up atual',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = (new PokerPhaseTenHeadsUpAuditService())->forTable($table, true);
        $areas = array_column($payload['blockers'], 'area');

        $this->assertContains('turn-engine', $areas);
        $this->assertContains('betting-state', $areas);
        $this->assertContains('bot-automation', $areas);
        $this->assertContains('showdown', $areas);
        $this->assertFalse($payload['isMultiSeatCandidate']);
        $this->assertGreaterThanOrEqual(3, count($payload['safeNextSteps']));
        $this->assertContains('Preservar showdown terminal sem ações habilitadas, conforme hotfix da FASE 9.3.1.', $payload['regressionLocks']);
    }
}
