<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerPhaseEightClosureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPhaseEightClosureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fechamento_da_fase_oito_mantem_tres_mais_bloqueado_no_lobby(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa preparada para fase futura',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $payload = (new PokerPhaseEightClosureService())->forLobbyTable($table);

        $this->assertSame('8.6', $payload['phase']);
        $this->assertSame('heads_up_with_multi_seat_contract', $payload['engineMode']);
        $this->assertFalse($payload['threePlusEnabled']);
        $this->assertSame('FASE 9 — motor multi-seat real', $payload['nextPhase']);
        $this->assertCount(6, $payload['checklist']);
        $this->assertSame('Assentos e presença dos jogadores', $payload['checklist'][0]['label']);
        $this->assertSame('Showdown e hierarquia de mãos blindados', $payload['checklist'][4]['label']);
        $this->assertContains('3+ jogadores ainda bloqueado por segurança', array_column($payload['phaseChecklist'], 'label'));
    }

    public function test_fechamento_da_fase_oito_centraliza_checklist_do_modo_local(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa local clássica',
            'status' => 'active',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = (new PokerPhaseEightClosureService())->forLocalTable($table);

        $this->assertSame('8.6', $payload['phase']);
        $this->assertSame('local_heads_up', $payload['engineMode']);
        $this->assertFalse($payload['threePlusEnabled']);
        $this->assertContains('Fluxo de nova mão local', array_column($payload['checklist'], 'label'));
        $this->assertGreaterThanOrEqual(4, count($payload['lockedDecisions']));
    }
}
