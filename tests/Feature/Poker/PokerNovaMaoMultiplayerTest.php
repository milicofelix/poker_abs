<?php

namespace Tests\Feature\Poker;

use App\Events\Poker\PokerTableStateUpdated;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class PokerNovaMaoMultiplayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_da_mesa_envia_url_para_iniciar_nova_mao_sincronizada(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.newHandActionUrl', route('poker.tables.new-hand', $table)));
    }

    public function test_jogador_sentado_consegue_iniciar_nova_mao_para_toda_a_mesa(): void
    {
        Event::fake([PokerTableStateUpdated::class]);

        $userOne = User::factory()->create(['name' => 'Jogador 1']);
        $userTwo = User::factory()->create(['name' => 'Jogador 2']);
        $table = $this->createTable();

        $this->seatPlayer($table, $userOne, 1);
        $this->seatPlayer($table, $userTwo, 2);

        $this->actingAs($userOne)->get(route('poker.tables.show', $table))->assertOk();

        PokerHand::query()->latest('id')->firstOrFail()->forceFill([
            'status' => 'finished',
            'finished_at' => now(),
        ])->save();

        $response = $this->actingAs($userOne)
            ->postJson(route('poker.tables.new-hand', $table));

        $response->assertOk()
            ->assertJsonPath('message', 'Nova mão iniciada para todos os jogadores.')
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('players.0.seatNumber', 1)
            ->assertJsonPath('players.1.seatNumber', 2);

        $this->assertSame(1, PokerHand::query()->where('status', 'running')->count());

        Event::assertDispatched(
            PokerTableStateUpdated::class,
            fn (PokerTableStateUpdated $event): bool => $event->tableId === $table->id
                && ($event->state['street'] ?? null) === 'pre_flop'
                && ($event->state['isFinished'] ?? true) === false,
        );
    }

    public function test_nao_inicia_nova_mao_com_apenas_um_jogador_sentado(): void
    {
        $user = User::factory()->create(['name' => 'Jogador 1']);
        $table = $this->createTable();

        $this->seatPlayer($table, $user, 1);

        $this->actingAs($user)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertStatus(422)
            ->assertJsonPath('message', 'A mesa precisa de dois jogadores sentados para iniciar uma nova mão.');
    }

    private function createTable(): PokerTable
    {
        return PokerTable::query()->create([
            'name' => 'Mesa nova mão',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);
    }

    private function seatPlayer(PokerTable $table, User $user, int $seatNumber): void
    {
        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => $seatNumber,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
