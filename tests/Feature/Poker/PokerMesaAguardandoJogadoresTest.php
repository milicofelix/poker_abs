<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerMesaAguardandoJogadoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_mesa_nao_inicia_mao_automaticamente(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('poker.tables.store'))
            ->assertRedirect();

        $table = PokerTable::query()->latest('id')->firstOrFail();

        $this->assertSame('waiting', $table->status);
        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }

    public function test_tela_da_mesa_sem_dois_jogadores_sentados_fica_aguardando_sem_timer_e_sem_acao(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = PokerTable::query()->create([
            'name' => 'Mesa aguardando',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Play')
                ->where('hand.isWaitingForPlayers', true)
                ->where('hand.street', 'waiting')
                ->where('hand.turnTimer', null)
                ->where('hand.canAct', false)
                ->where('hand.waitingForPlayers.playersSeated', 0)
                ->where('hand.waitingForPlayers.playersNeeded', 2));

        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }

    public function test_primeira_mao_inicia_quando_jogador_sentado_adiciona_um_bot(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = PokerTable::query()->create([
            'name' => 'Mesa pronta',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true)
            ->assertJsonPath('state.turnTimer', null)
            ->assertJsonPath('state.canAct', false);

        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'conservative',
                'difficulty' => 'normal',
            ])
            ->assertOk()
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonPath('state.turnTimer.isExpired', false);

        $this->assertSame('playing', $table->fresh()->status);
        $this->assertSame(1, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }
    public function test_botao_nova_mao_em_mesa_sem_jogadores_retorna_estado_aguardando_sem_erro_422(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = PokerTable::query()->create([
            'name' => 'Mesa sem oponente',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true)
            ->assertJsonPath('state.street', 'waiting')
            ->assertJsonPath('state.turnTimer', null)
            ->assertJsonPath('state.canAct', false);

        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }


    public function test_bot_sentado_nao_fica_offline_por_inatividade_e_conta_para_nova_mao(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $botUser = User::factory()->create(['name' => 'Bot Conservador']);
        $table = PokerTable::query()->create([
            'name' => 'Mesa com bot antigo',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => 'Adriano',
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'is_bot' => false,
            'joined_at' => now()->subMinute(),
            'last_seen_at' => now(),
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $botUser->id,
            'nickname' => 'Bot Conservador',
            'stack' => 1000,
            'seat_number' => 2,
            'status' => 'online',
            'is_bot' => true,
            'bot_profile' => 'conservative',
            'bot_difficulty' => 'normal',
            'joined_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', false)
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonPath('state.turnTimer.isExpired', false);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $botUser->id,
            'status' => 'online',
            'is_bot' => true,
        ]);
    }

}
