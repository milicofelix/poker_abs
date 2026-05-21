<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMesaAutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_tela_da_mesa(): void
    {
        $table = $this->createTable();

        $this->get(route('poker.tables.show', $table))
            ->assertRedirect(route('login'));
    }

    public function test_visitante_nao_acessa_estado_ou_acoes_da_mesa_por_json(): void
    {
        $table = $this->createTable();

        $this->getJson(route('poker.tables.state', $table))->assertUnauthorized();
        $this->postJson(route('poker.tables.actions', $table), ['action' => 'check'])->assertUnauthorized();
        $this->postJson(route('poker.tables.timeout', $table))->assertUnauthorized();
        $this->postJson(route('poker.tables.join', $table))->assertUnauthorized();
        $this->postJson(route('poker.tables.seat', $table), ['seat_number' => 1])->assertUnauthorized();
        $this->postJson(route('poker.tables.new-hand', $table))->assertUnauthorized();
        $this->postJson(route('poker.tables.bots', $table), ['seat_number' => 2])->assertUnauthorized();
    }

    public function test_usuario_logado_pode_visualizar_mesa_sem_ficar_sentado_automaticamente(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk();

        $this->assertSame(0, $table->players()->count());
    }

    private function createTable(): PokerTable
    {
        return PokerTable::query()->create([
            'name' => 'Mesa protegida',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);
    }
}
