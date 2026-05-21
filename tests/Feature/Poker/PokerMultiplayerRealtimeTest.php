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

    public function test_evento_da_mesa_envia_payload_compacto_para_nao_estourar_limite_do_websocket(): void
    {
        $this->get('/poker')->assertOk();

        $table = PokerTable::query()->firstOrFail();

        $state = [
            'street' => 'turn',
            'streetLabel' => 'Turn',
            'isFinished' => false,
            'persistence' => [
                'tableId' => $table->id,
                'syncVersion' => 15,
            ],
            'actionHistory' => array_fill(0, 80, [
                'actor' => 'opponent',
                'action' => 'raise',
                'message' => str_repeat('ação acumulada no histórico ', 20),
                'botContext' => [
                    'memory' => str_repeat('contexto pesado ', 50),
                ],
            ]),
            'lastAction' => [
                'actor' => 'opponent',
                'actorLabel' => 'Bot Maniac',
                'action' => 'raise',
                'amount' => 120,
                'message' => str_repeat('mensagem longa do bot ', 40),
                'botContext' => [
                    'memory' => str_repeat('payload grande ', 80),
                ],
                'botStats' => [
                    'totalDecisions' => 99,
                ],
                'botMemory' => [
                    'notes' => array_fill(0, 20, 'nota extensa'),
                ],
            ],
        ];

        $payload = (new PokerTableStateUpdated($table->id, $state))->broadcastWith();

        $this->assertSame($table->id, $payload['tableId']);
        $this->assertSame(15, $payload['state']['persistence']['syncVersion']);
        $this->assertSame('turn', $payload['state']['street']);
        $this->assertSame('raise', $payload['state']['lastAction']['action']);
        $this->assertArrayNotHasKey('actionHistory', $payload['state']);
        $this->assertArrayNotHasKey('botContext', $payload['state']['lastAction']);
        $this->assertArrayNotHasKey('botStats', $payload['state']['lastAction']);
        $this->assertLessThan(1500, strlen(json_encode($payload)));
    }


    public function test_evento_realtime_multi_seat_envia_sinal_leve_sem_dados_privados(): void
    {
        $this->get('/poker')->assertOk();

        $table = PokerTable::query()->firstOrFail();

        $payload = (new PokerTableStateUpdated($table->id, [
            'street' => 'river',
            'isFinished' => false,
            'persistence' => [
                'tableId' => $table->id,
                'syncVersion' => 22,
            ],
            'multiSeat' => [
                'enabled' => true,
                'currentSeat' => 3,
                'players' => [
                    ['seatNumber' => 1, 'cards' => [['label' => 'A♠']]],
                    ['seatNumber' => 2, 'cards' => [['label' => 'K♠']]],
                    ['seatNumber' => 3, 'cards' => [['label' => 'Q♠']]],
                ],
            ],
        ]))->broadcastWith();

        $this->assertSame('10.16', $payload['state']['multiSeatRealtime']['phase']);
        $this->assertSame(3, $payload['state']['multiSeatRealtime']['currentSeat']);
        $this->assertSame([1, 2, 3], $payload['state']['multiSeatRealtime']['activeSeatNumbers']);
        $this->assertTrue($payload['state']['multiSeatRealtime']['requiresHydration']);
        $this->assertArrayNotHasKey('multiSeat', $payload['state']);
        $this->assertLessThan(1500, strlen(json_encode($payload)));
    }

}
