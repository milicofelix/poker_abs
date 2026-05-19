<?php

namespace Tests\Feature\Poker;

use App\Events\Poker\PokerTableStateUpdated;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PokerSincronizacaoMesaTest extends TestCase
{
    use RefreshDatabase;
    use CreatesActivePokerTable;

    public function test_acao_da_mesa_usa_o_estado_salvo_no_banco_e_nao_um_estado_local_da_aba(): void
    {
        $table = $this->createActivePokerTable();
        $hand = PokerHand::query()->firstOrFail();
        $originalState = $hand->state_payload;

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('poker.tables.actions', $table), [
            'state' => [
                ...$originalState,
                'pot' => 9999,
                'playerStack' => 1,
            ],
            'action' => 'call',
        ]);

        $response->assertOk();

        $nextState = $response->json('state');

        $this->assertSame('flop', $nextState['street']);
        $this->assertNotSame(9999, $nextState['pot']);
        $this->assertNotSame(1, $nextState['playerStack']);
        $this->assertSame($table->id, $nextState['persistence']['tableId']);
    }

    public function test_acao_da_mesa_incrementa_a_versao_de_sincronizacao_e_dispara_broadcast(): void
    {
        Event::fake([PokerTableStateUpdated::class]);

        $table = $this->createActivePokerTable();

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
        ]);

        $response->assertOk();

        $this->assertSame(2, $response->json('state.persistence.syncVersion'));

        Event::assertDispatched(
            PokerTableStateUpdated::class,
            fn (PokerTableStateUpdated $event): bool => $event->tableId === $table->id
                && $event->state['persistence']['syncVersion'] === 2,
        );
    }

    public function test_mesa_sem_mao_ativa_retorna_404_para_acao_sincronizada(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa vazia',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('poker.tables.actions', $table), [
            'action' => 'call',
        ])->assertNotFound();
    }
}
