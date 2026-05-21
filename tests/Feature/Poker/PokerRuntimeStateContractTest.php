<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerRuntimeStateContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_estado_runtime_expõe_contrato_heads_up_sem_ligar_multi_seat(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa contrato runtime',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('engineMode', 'heads_up')
            ->assertJsonPath('multiSeatEnabled', false)
            ->assertJsonPath('stateContracts.active', 'heads_up')
            ->assertJsonPath('stateContracts.headsUp.isCurrentEngine', true)
            ->assertJsonPath('stateContracts.multiSeat.isEnabled', false)
            ->assertJsonPath('stateContracts.compatibility.headsUpStillAuthoritative', true)
            ->assertJsonPath('stateContracts.compatibility.canStartThreePlusPlayers', false);
    }

    public function test_estado_runtime_preserva_mao_finalizada_antes_de_avaliar_prontidao(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa showdown preservado',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        PokerHand::query()->create([
            'poker_table_id' => $table->id,
            'code' => (string) Str::uuid(),
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => 120,
            'current_bet' => 0,
            'dealer_position' => 1,
            'state_payload' => [
                'isFinished' => true,
                'street' => 'showdown',
                'pot' => 120,
                'conclusion' => [
                    'message' => 'Mão finalizada e preservada pelo contrato runtime.',
                ],
            ],
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.isFinished', true)
            ->assertJsonPath('state.street', 'showdown')
            ->assertJsonPath('state.conclusion.message', 'Mão finalizada e preservada pelo contrato runtime.')
            ->assertJsonPath('stateContracts.headsUp.state.isFinished', true);

        $this->assertSame(1, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }
}
