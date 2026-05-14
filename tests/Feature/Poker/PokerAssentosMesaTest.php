<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerAssentosMesaTest extends TestCase
{
    use RefreshDatabase;


    public function test_tela_da_mesa_envia_fluxo_de_assentos_para_o_frontend(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa com slots',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Play')
                ->where('table.joinUrl', route('poker.tables.join', $table))
                ->where('table.seatUrl', route('poker.tables.seat', $table))
                ->where('table.maxPlayers', 2)
                ->where('table.currentUserId', $user->id)
                ->has('table.seatSlots', 2)
                ->where('table.seatSlots.0.seatNumber', 1)
                ->where('table.seatSlots.0.status', 'available')
                ->where('table.seatSlots.1.seatNumber', 2)
                ->where('table.seatSlots.1.status', 'available'));
    }

    public function test_jogador_consegue_sentar_em_um_assento_livre(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa com assentos',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table));

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('player.seatNumber', 3)
            ->assertJsonPath('seats.0.seatNumber', 3);

        $this->assertDatabaseHas('poker_table_players', [
            'user_id' => $user->id,
            'seat_number' => 3,
        ]);
    }

    public function test_nao_permite_dois_jogadores_no_mesmo_assento(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        $table = PokerTable::query()->create([
            'name' => 'Mesa bloqueada',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.join', $table));

        $this->actingAs($userTwo)
            ->postJson(route('poker.tables.join', $table));

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 1,
            ])
            ->assertOk();

        $this->actingAs($userTwo)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Este assento já está ocupado.');
    }

    public function test_jogador_sem_assento_fica_bloqueado_ate_escolher_um_slot(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa fluxo correto',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('hand.multiplayerPerspective.role', 'waiting_seat')
                ->where('hand.currentTurn.isCurrentUserTurn', false)
                ->where('hand.canAct', false)
                ->where('hand.currentTurn.message', 'Escolha um assento livre para participar da mão.'));

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('state.multiplayerPerspective.role', 'player')
            ->assertJsonPath('state.multiplayerPerspective.seatNumber', 1)
            ->assertJsonPath('state.playersContext.current.seatNumber', 1)
            ->assertJsonPath('seatSlots.0.status', 'occupied')
            ->assertJsonPath('seatSlots.0.player.nickname', 'Adriano');
    }

    public function test_nao_permite_sentar_fora_do_limite_de_assentos_da_mesa(): void
    {
        $user = User::factory()->create();

        $table = PokerTable::query()->create([
            'name' => 'Mesa limite de assentos',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 3,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Assento inválido para esta mesa.');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'seat_number' => null,
        ]);
    }

}
