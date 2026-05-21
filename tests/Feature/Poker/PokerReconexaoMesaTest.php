<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerReconexaoMesaTest extends TestCase
{
    use RefreshDatabase;
    use CreatesActivePokerTable;

    public function test_pode_buscar_o_estado_atual_da_mesa_para_reconectar_a_aba(): void
    {
        $table = $this->createActivePokerTable();
        $hand = PokerHand::query()->firstOrFail();
        $state = $hand->state_payload;

        $response = $this->actingAs(User::factory()->create())
            ->getJson(route('poker.tables.state', $table));

        $response->assertOk();
        $response->assertJsonPath('state.persistence.tableId', $table->id);
        $response->assertJsonPath('state.persistence.handId', $hand->id);
        $response->assertJsonPath('state.persistence.syncVersion', $state['persistence']['syncVersion']);
    }

    public function test_busca_de_estado_retorna_estado_de_espera_quando_a_mesa_nao_tem_mao_ativa(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa aguardando jogadores',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true)
            ->assertJsonPath('state.turnTimer', null)
            ->assertJsonPath('state.canAct', false);
    }

    public function test_tela_da_mesa_envia_url_de_reidratacao_para_o_frontend(): void
    {
        $table = $this->createActivePokerTable();

        $this->actingAs(User::factory()->create())
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.stateUrl', route('poker.tables.state', $table))
                ->where('table.actionUrl', route('poker.tables.actions', $table)));
    }
}
