<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerFaseDezContratosMultiSeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_mesa_com_capacidade_maior_que_heads_up_sem_ativar_motor_tres_mais(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);

        $response = $this
            ->actingAs($user)
            ->post(route('poker.tables.store'), [
                'name' => 'Mesa 4 jogadores',
                'max_players' => 4,
            ]);

        $table = PokerTable::query()->firstOrFail();

        $response->assertRedirect(route('poker.tables.show', $table));

        $this->assertSame(4, $table->declaredMaxPlayers());
        $this->assertSame(2, $table->currentEngineMaxPlayers());
        $this->assertTrue($table->isMultiSeatCandidate());
        $this->assertSame('multi_seat_preparation', $table->engineMode());
    }

    public function test_lobby_envia_opcoes_de_capacidade_para_criacao_de_mesas(): void
    {
        $this->get(route('poker.lobby'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Lobby')
                ->where('tableCreation.defaultMaxPlayers', 2)
                ->where('tableCreation.currentEngineMaxPlayers', 2)
                ->where('tableCreation.futureMultiSeatTarget', 6)
                ->has('tableCreation.maxPlayersOptions', 5)
                ->where('tableCreation.maxPlayersOptions.0.value', 2)
                ->where('tableCreation.maxPlayersOptions.0.isCurrentEngine', true)
                ->where('tableCreation.maxPlayersOptions.1.value', 3)
                ->where('tableCreation.maxPlayersOptions.1.isMultiSeatCandidate', true));
    }

    public function test_lobby_expoe_mesa_tres_mais_com_capacidade_declarada_e_modo_preparado(): void
    {
        PokerTable::query()->create([
            'name' => 'Mesa multi-seat visivel',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 5,
        ]);

        $this->get(route('poker.lobby'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tables.0.name', 'Mesa multi-seat visivel')
                ->where('tables.0.maxPlayers', 5)
                ->where('tables.0.availableSeats', 5)
                ->where('tables.0.capacity.isMultiSeatCandidate', true)
                ->where('tables.0.capacity.engineMode', 'multi_seat_preparation')
                ->where('tables.0.capacity.currentEngineMaxPlayers', 2));
    }

    public function test_permite_entrada_de_jogadores_ate_a_capacidade_declarada(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa quatro lugares',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $users = User::factory()->count(5)->create();

        foreach ($users->take(4) as $user) {
            $this->actingAs($user)
                ->postJson(route('poker.tables.join', $table))
                ->assertOk();
        }

        $this->actingAs($users->last())
            ->postJson(route('poker.tables.join', $table))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Mesa cheia. Escolha outra mesa no lobby.');

        $this->assertSame(4, $table->realPlayers()->count());
    }

    public function test_mesa_tres_mais_entrega_slots_para_todos_os_assentos_declarados(): void
    {
        $user = User::factory()->create();

        $table = PokerTable::query()->create([
            'name' => 'Mesa seis slots',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('table.maxPlayers', 6)
                ->where('table.phaseTenAudit.threePlusEnabled', false)
                ->where('table.phaseTenAudit.contractStatus', 'preparation_only')
                ->has('table.seatSlots', 6)
                ->where('table.seatSlots.5.seatNumber', 6)
                ->where('table.seatSlots.5.status', 'available'));
    }


    public function test_estado_da_mesa_expoe_preview_de_turnos_circulares_sem_ativar_motor_tres_mais(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa turnos circulares',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $users = User::factory()->count(3)->create();

        foreach ($users as $index => $user) {
            $this->actingAs($user)
                ->postJson(route('poker.tables.join', $table))
                ->assertOk();
        }

        $this->actingAs($users->first())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('multiSeatEnabled', false)
            ->assertJsonPath('stateContracts.active', 'heads_up')
            ->assertJsonPath('stateContracts.multiSeat.turnCyclePreview.phase', '10.4')
            ->assertJsonPath('stateContracts.multiSeat.turnCyclePreview.enabledInMainEngine', false)
            ->assertJsonPath('stateContracts.multiSeat.turnCyclePreview.dealerSeat', 1)
            ->assertJsonPath('stateContracts.multiSeat.turnCyclePreview.smallBlindSeat', 2)
            ->assertJsonPath('stateContracts.multiSeat.turnCyclePreview.bigBlindSeat', 3)
            ->assertJsonPath('stateContracts.multiSeat.turnCyclePreview.firstPreFlopSeat', 1);
    }


    public function test_estado_da_mesa_expoe_preview_de_rodada_de_apostas_sem_ativar_motor_tres_mais(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa rodada apostas multi-seat',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $this->actingAs($user)
                ->postJson(route('poker.tables.join', $table))
                ->assertOk();
        }

        $this->actingAs($users->first())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('multiSeatEnabled', false)
            ->assertJsonPath('stateContracts.active', 'heads_up')
            ->assertJsonPath('stateContracts.multiSeat.bettingRoundPreview.phase', '10.6')
            ->assertJsonPath('stateContracts.multiSeat.bettingRoundPreview.enabledInMainEngine', false)
            ->assertJsonPath('stateContracts.multiSeat.bettingRoundPreview.canCloseStreet', false)
            ->assertJsonPath('stateContracts.multiSeat.bettingRoundPreview.activeContenders', 3);
    }


    public function test_estado_da_mesa_expoe_preview_de_showdown_sem_ativar_motor_tres_mais(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa showdown multi-seat',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $this->actingAs($user)
                ->postJson(route('poker.tables.join', $table))
                ->assertOk();
        }

        $this->actingAs($users->first())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('multiSeatEnabled', false)
            ->assertJsonPath('stateContracts.active', 'heads_up')
            ->assertJsonPath('stateContracts.multiSeat.showdownPreview.phase', '10.7')
            ->assertJsonPath('stateContracts.multiSeat.showdownPreview.enabledInMainEngine', false)
            ->assertJsonPath('stateContracts.multiSeat.showdownPreview.activeContenders', 3)
            ->assertJsonPath('stateContracts.multiSeat.showdownPreview.canResolveShowdown', false);
    }


    public function test_tela_da_mesa_envia_contratos_para_integracao_visual_multi_seat(): void
    {
        $user = User::factory()->create();

        $table = PokerTable::query()->create([
            'name' => 'Mesa visual multi-seat',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Play')
                ->where('table.stateContracts.active', 'heads_up')
                ->where('table.stateContracts.compatibility.multiSeatEnabled', false)
                ->where('table.stateContracts.multiSeat.declaredMaxPlayers', 4)
                ->where('table.stateContracts.multiSeat.dealPreview.phase', '10.3')
                ->where('table.stateContracts.multiSeat.turnCyclePreview.phase', '10.4')
                ->where('table.stateContracts.multiSeat.actionPreview.phase', '10.5')
                ->where('table.stateContracts.multiSeat.bettingRoundPreview.phase', '10.6')
                ->where('table.stateContracts.multiSeat.showdownPreview.phase', '10.7'));
    }

    public function test_nao_permite_criar_mesa_acima_do_alvo_multi_seat_da_fase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('poker.lobby'))
            ->post(route('poker.tables.store'), [
                'name' => 'Mesa invalida',
                'max_players' => 7,
            ])
            ->assertRedirect(route('poker.lobby'))
            ->assertSessionHasErrors('max_players');

        $this->assertSame(0, PokerTable::query()->count());
    }
}
