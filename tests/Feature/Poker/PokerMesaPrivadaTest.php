<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerMesaPrivadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_logado_pode_criar_mesa_privada_com_codigo_de_convite(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);

        $response = $this
            ->actingAs($user)
            ->post('/poker/tables', [
                'name' => 'Mesa secreta',
                'is_private' => true,
            ]);

        $table = PokerTable::query()->firstOrFail();

        $response->assertRedirect(route('poker.tables.show', $table));

        $this->assertSame('Mesa secreta', $table->name);
        $this->assertTrue((bool) $table->is_private);
        $this->assertNotEmpty($table->invite_code);
        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'status' => 'online',
        ]);
    }

    public function test_mesa_privada_nao_aparece_no_lobby_publico(): void
    {
        PokerTable::query()->create([
            'name' => 'Mesa pública',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
            'is_private' => false,
        ]);

        PokerTable::query()->create([
            'name' => 'Mesa escondida',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
            'is_private' => true,
            'invite_code' => 'PRIV1234',
        ]);

        $this->get('/poker/lobby')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Lobby')
                ->has('tables', 1)
                ->where('tables.0.name', 'Mesa pública'))
            ->assertDontSee('Mesa escondida');
    }

    public function test_usuario_pode_entrar_em_mesa_privada_por_codigo(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create(['name' => 'Convidado']);
        $table = PokerTable::query()->create([
            'name' => 'Mesa privada',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
            'is_private' => true,
            'invite_code' => 'ABC12345',
        ]);

        $table->realPlayers()->create([
            'user_id' => $owner->id,
            'nickname' => $owner->name,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this
            ->actingAs($guest)
            ->post('/poker/private-tables/join', ['invite_code' => 'abc12345'])
            ->assertRedirect(route('poker.tables.show', $table));

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $guest->id,
            'nickname' => 'Convidado',
            'status' => 'online',
        ]);
    }

    public function test_codigo_privado_invalido_retorna_ao_lobby_com_erro(): void
    {
        $user = User::factory()->create();

        $this
            ->from('/poker/lobby')
            ->actingAs($user)
            ->post('/poker/private-tables/join', ['invite_code' => 'ERRADO'])
            ->assertRedirect('/poker/lobby')
            ->assertSessionHas('error', 'Código de mesa privada inválido ou expirado.');
    }
}
