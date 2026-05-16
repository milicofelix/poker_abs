<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class PokerTurnTimerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesActivePokerTable;

    public function test_nova_mesa_inicia_mao_com_turn_timer_sincronizado(): void
    {
        Carbon::setTestNow('2026-05-13 20:00:00');

        $this->createActivePokerTable();

        $state = PokerHand::query()->firstOrFail()->state_payload;

        $this->assertSame(30, $state['turnTimer']['secondsTotal']);
        $this->assertSame('2026-05-13T20:00:00-03:00', $state['turnTimer']['startedAt']);
        $this->assertSame('2026-05-13T20:00:30-03:00', $state['turnTimer']['expiresAt']);
        $this->assertFalse($state['turnTimer']['isExpired']);
    }

    public function test_estado_da_mesa_informa_timer_expirado_quando_o_prazo_passou(): void
    {
        Carbon::setTestNow('2026-05-13 20:00:00');

        $table = $this->createActivePokerTable();

        Carbon::setTestNow('2026-05-13 20:00:35');

        $response = $this->getJson(route('poker.tables.state', $table));

        $response->assertOk();
        $response->assertJsonPath('state.turnTimer.secondsRemaining', 0);
        $response->assertJsonPath('state.turnTimer.isExpired', true);
    }

    public function test_acao_da_mesa_reinicia_o_turn_timer_para_a_proxima_jogada(): void
    {
        Carbon::setTestNow('2026-05-13 20:00:00');

        $table = $this->createActivePokerTable();

        Carbon::setTestNow('2026-05-13 20:00:10');

        $response = $this->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
        ]);

        $response->assertOk();
        $response->assertJsonPath('state.turnTimer.startedAt', '2026-05-13T20:00:10-03:00');
        $response->assertJsonPath('state.turnTimer.expiresAt', '2026-05-13T20:00:40-03:00');
        $response->assertJsonPath('state.turnTimer.isExpired', false);
    }
}
