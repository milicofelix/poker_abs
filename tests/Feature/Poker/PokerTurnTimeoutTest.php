<?php

namespace Tests\Feature\Poker;

use App\Events\Poker\PokerTableStateUpdated;
use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PokerTurnTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeout_expirado_executa_fold_automatico_quando_existe_aposta_pendente(): void
    {
        Event::fake([PokerTableStateUpdated::class]);
        Carbon::setTestNow('2026-05-13 20:00:00');

        $this->post('/poker/tables');

        $table = PokerTable::query()->firstOrFail();

        Carbon::setTestNow('2026-05-13 20:00:31');

        $response = $this->postJson(route('poker.tables.timeout', $table));

        $response->assertOk();
        $response->assertJsonPath('processed', true);
        $response->assertJsonPath('action', 'fold');
        $response->assertJsonPath('state.turnTimeout.processed', true);
        $response->assertJsonPath('state.turnTimeout.label', 'Fold automático');
        $response->assertJsonPath('state.lastAction.isAutomatic', true);
        $response->assertJsonPath('state.isFinished', true);

        $this->assertDatabaseHas('poker_hands', [
            'poker_table_id' => $table->id,
            'status' => 'finished',
        ]);

        $this->assertSame('Fold', PokerActionLog::query()->latest('id')->firstOrFail()->action);

        Event::assertDispatched(
            PokerTableStateUpdated::class,
            fn (PokerTableStateUpdated $event): bool => $event->tableId === $table->id
                && $event->state['turnTimeout']['action'] === 'fold',
        );
    }

    public function test_timeout_nao_processa_acao_quando_timer_ainda_esta_ativo(): void
    {
        Event::fake([PokerTableStateUpdated::class]);
        Carbon::setTestNow('2026-05-13 20:00:00');

        $this->post('/poker/tables');

        $table = PokerTable::query()->firstOrFail();

        Carbon::setTestNow('2026-05-13 20:00:10');

        $response = $this->postJson(route('poker.tables.timeout', $table));

        $response->assertOk();
        $response->assertJsonPath('processed', false);
        $response->assertJsonPath('action', null);
        $response->assertJsonPath('state.turnTimer.isExpired', false);

        $this->assertSame(0, PokerActionLog::query()->count());
        Event::assertNotDispatched(PokerTableStateUpdated::class);
    }

    public function test_timeout_expirado_executa_check_automatico_quando_nao_existe_aposta_pendente(): void
    {
        Carbon::setTestNow('2026-05-13 20:00:00');

        $this->post('/poker/tables');

        $table = PokerTable::query()->firstOrFail();
        $hand = PokerHand::query()->firstOrFail();
        $state = $hand->state_payload;
        $state['street'] = 'flop';
        $state['streetLabel'] = 'Flop';
        $state['currentBet'] = 0;
        $state['playerStreetBet'] = 0;
        $state['opponentStreetBet'] = 0;
        $state['amountToCall'] = 0;
        $state['canCheck'] = true;
        $state['canCall'] = false;
        $state['turnTimer']['startedAt'] = '2026-05-13T20:00:00-03:00';
        $state['turnTimer']['expiresAt'] = '2026-05-13T20:00:30-03:00';

        $hand->forceFill([
            'street' => 'flop',
            'current_bet' => 0,
            'state_payload' => $state,
        ])->save();

        Carbon::setTestNow('2026-05-13 20:00:31');

        $response = $this->postJson(route('poker.tables.timeout', $table));

        $response->assertOk();
        $response->assertJsonPath('processed', true);
        $response->assertJsonPath('action', 'check');
        $response->assertJsonPath('state.turnTimeout.label', 'Check automático');
        $response->assertJsonPath('state.lastAction.isAutomatic', true);
        $this->assertSame('Check', PokerActionLog::query()->oldest('id')->firstOrFail()->action);
    }
}
