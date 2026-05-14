<?php

namespace Tests\Feature\Poker;

use App\Events\Poker\PokerTableStateUpdated;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PokerMultiplayerRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_acao_da_rodada_dispara_evento_de_sincronizacao_da_mesa(): void
    {
        Event::fake([PokerTableStateUpdated::class]);

        $this->get('/poker')->assertOk();

        $table = PokerTable::query()->firstOrFail();

        $this->postJson('/poker/actions', [
            'action' => 'call',
        ])->assertOk();

        Event::assertDispatched(
            PokerTableStateUpdated::class,
            fn (PokerTableStateUpdated $event): bool => $event->tableId === $table->id
                && $event->state['street'] === 'flop'
                && $event->broadcastAs() === 'poker.table.state.updated'
                && $event->broadcastWith()['tableId'] === $table->id,
        );
    }

    public function test_evento_da_mesa_usa_canal_publico_para_primeiro_multiplayer_guest(): void
    {
        $this->get('/poker')->assertOk();

        $table = PokerTable::query()->firstOrFail();

        $event = new PokerTableStateUpdated($table->id, ['street' => 'pre_flop']);

        $this->assertSame('poker.tables.'.$table->id, $event->broadcastOn()->name);
    }
}
