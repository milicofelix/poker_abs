<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerJogadoresReaisPorMesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_consegue_entrar_como_jogador_real_na_mesa(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = PokerTable::query()->create([
            'name' => 'Mesa multiplayer',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk()
            ->assertJsonPath('message', 'Jogador entrou na mesa com sucesso.')
            ->assertJsonPath('player.nickname', 'Adriano')
            ->assertJsonPath('players.0.nickname', 'Adriano')
            ->assertJsonPath('players.0.status', 'online');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => 'Adriano',
            'status' => 'online',
        ]);
    }

    public function test_usuario_nao_duplica_entrada_na_mesma_mesa(): void
    {
        $user = User::factory()->create();
        $table = PokerTable::query()->create([
            'name' => 'Mesa sem duplicidade',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
        $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();

        $this->assertSame(1, PokerTablePlayer::query()->count());
        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'status' => 'online',
        ]);
    }

    public function test_visitante_nao_consegue_entrar_como_jogador_real(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa protegida',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->postJson(route('poker.tables.join', $table))
            ->assertUnauthorized();

        $this->assertSame(0, PokerTablePlayer::query()->count());
    }
}
