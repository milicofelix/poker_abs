<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerLobbyMesasTest extends TestCase
{
    use RefreshDatabase;
    use CreatesActivePokerTable;

    public function test_pode_abrir_o_lobby_de_mesas(): void
    {
        PokerTable::query()->create([
            'name' => 'Mesa teste',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->get('/poker/lobby')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Lobby')
                ->has('tables', 1)
                ->where('tables.0.name', 'Mesa teste'));
    }

    public function test_pode_criar_mesa_pelo_lobby_e_redirecionar_para_a_mesa(): void
    {
        $response = $this->post('/poker/tables');

        $table = PokerTable::query()->firstOrFail();

        $response->assertRedirect(route('poker.tables.show', $table));

        $this->assertSame('waiting', $table->status);
        $this->assertDatabaseMissing('poker_hands', [
            'poker_table_id' => $table->id,
            'status' => 'running',
        ]);
    }

    public function test_duas_abas_da_mesma_url_carregam_o_mesmo_estado_da_mao(): void
    {
        $table = $this->createActivePokerTable();
        $hand = PokerHand::query()->firstOrFail();

        $firstResponse = $this->get(route('poker.tables.show', $table));
        $secondResponse = $this->get(route('poker.tables.show', $table));

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $this->assertSame(1, PokerHand::query()->count());
        $this->assertSame($hand->id, PokerHand::query()->firstOrFail()->id);
    }
}
