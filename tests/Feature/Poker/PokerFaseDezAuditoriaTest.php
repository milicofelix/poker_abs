<?php

namespace Tests\Feature\Poker;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerFaseDezAuditoriaTest extends TestCase
{
    use CreatesActivePokerTable;
    use RefreshDatabase;

    public function test_tela_da_mesa_envia_auditoria_da_fase_dez_sem_ativar_tres_mais(): void
    {
        $user = User::factory()->create();
        $table = $this->createActivePokerTable(['max_players' => 2]);

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Poker/Play')
                ->where('table.phaseTenAudit.phase', '10.1')
                ->where('table.phaseTenAudit.activeEngine', 'heads_up')
                ->where('table.phaseTenAudit.targetEngine', 'multi_seat')
                ->where('table.phaseTenAudit.threePlusEnabled', false)
                ->where('table.phaseTenAudit.currentEngineMaxPlayers', 2)
                ->has('table.phaseTenAudit.blockers', 5)
                ->where('table.phaseTenAudit.blockers.0.area', 'turn-engine')
                ->where('table.phaseTenAudit.safeNextSteps.0.phase', '10.2')
            );
    }
}
