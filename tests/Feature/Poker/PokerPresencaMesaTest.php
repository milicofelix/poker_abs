<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPresencaMesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_da_mesa_envia_url_para_sair_da_mesa(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.leaveUrl', route('poker.tables.leave', $table)));
    }

    public function test_jogador_consegue_sair_da_mesa_e_liberar_o_assento(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
        $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
            'seat_number' => 1,
        ])->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.leave', $table))
            ->assertOk()
            ->assertJsonPath('message', 'Você saiu da mesa.')
            ->assertJsonPath('players.0.status', 'offline')
            ->assertJsonPath('players.0.seatNumber', null)
            ->assertJsonPath('seatSlots.0.status', 'available');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'status' => 'offline',
            'seat_number' => null,
        ]);
    }

    public function test_reidratacao_marca_jogador_atual_como_online_sem_duplicar_jogadores(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => 'Adriano',
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'offline',
            'joined_at' => now()->subMinutes(5),
            'last_seen_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user)->get(route('poker.tables.show', $table))->assertOk();

        $this->actingAs($user)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('players.0.status', 'online')
            ->assertJsonPath('players.0.seatNumber', 1);

        $this->assertSame(1, PokerTablePlayer::query()->count());
    }

    public function test_jogador_sem_heartbeat_fica_offline_sem_liberar_assento(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $viewer = User::factory()->create();
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => 'Adriano',
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now()->subMinutes(5),
            'last_seen_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($viewer)->get(route('poker.tables.show', $table))->assertOk();

        $this->actingAs($viewer)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('players.0.status', 'offline')
            ->assertJsonPath('seatSlots.0.status', 'occupied')
            ->assertJsonPath('seatSlots.0.player.nickname', 'Adriano');
    }


    public function test_tela_da_mesa_exibe_jogador_sentado_e_espectador_na_presenca_visual(): void
    {
        $seatedUser = User::factory()->create(['name' => 'Jogador Sentado']);
        $spectatorUser = User::factory()->create(['name' => 'Visitante Online']);
        $viewer = User::factory()->create(['name' => 'Viewer']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $seatedUser->id,
            'nickname' => 'Jogador Sentado',
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $spectatorUser->id,
            'nickname' => 'Visitante Online',
            'stack' => 1000,
            'seat_number' => null,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.realPlayers.0.nickname', 'Jogador Sentado')
                ->where('table.realPlayers.0.seatNumber', 1)
                ->where('table.realPlayers.0.status', 'online')
                ->where('table.realPlayers.1.nickname', 'Visitante Online')
                ->where('table.realPlayers.1.seatNumber', null)
                ->where('table.realPlayers.1.status', 'online')
                ->where('table.seatSlots.0.status', 'occupied')
                ->where('table.seatSlots.0.player.nickname', 'Jogador Sentado'));
    }

    private function createTable(): PokerTable
    {
        return PokerTable::query()->create([
            'name' => 'Mesa presença',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);
    }
}
