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
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true)
            ->assertJsonPath('state.canAct', false)
            ->assertJsonPath('message', 'A mesa precisa de mais jogador sentado para iniciar a mão.');
    }

    public function test_nova_mao_processa_bot_quando_o_bot_comeca_a_rodada(): void
    {
        $botUser = User::factory()->create(['name' => 'Bot Inicial']);
        $humanUser = User::factory()->create(['name' => 'Jogador Humano']);
        $table = $this->createTable();

        $this->seatPlayer($table, $botUser, 1, true);
        $this->seatPlayer($table, $humanUser, 2);

        $this->actingAs($humanUser)->get(route('poker.tables.show', $table))->assertOk();

        PokerHand::query()->latest('id')->firstOrFail()->forceFill([
            'status' => 'finished',
            'finished_at' => now(),
        ])->save();

        $this->actingAs($humanUser)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.botDecision.processed', true)
            ->assertJsonPath('state.currentTurn.canonicalActor', 'opponent')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', true)
            ->assertJsonPath('state.canAct', true);
    }


    public function test_nova_mao_bot_vs_bot_nao_processa_a_mao_inteira_no_request_inicial(): void
    {
        $spectator = User::factory()->create(['name' => 'Espectador']);
        $botOne = User::factory()->create(['name' => 'Bot 1']);
        $botTwo = User::factory()->create(['name' => 'Bot 2']);
        $table = $this->createTable();

        $this->seatPlayer($table, $botOne, 1, true);
        $this->seatPlayer($table, $botTwo, 2, true);

        $this->actingAs($spectator)->get(route('poker.tables.show', $table))->assertOk();

        PokerHand::query()->latest('id')->firstOrFail()->forceFill([
            'status' => 'finished',
            'state_payload' => [
                ...PokerHand::query()->latest('id')->firstOrFail()->state_payload,
                'isFinished' => true,
                'conclusion' => ['isFinished' => true],
            ],
            'finished_at' => now(),
        ])->save();

        $this->actingAs($spectator)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.botVsBotSimulation', true)
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.actionHistory', [])
            ->assertJsonPath('state.currentTurn.canonicalActor', 'player')
            ->assertJsonPath('state.turnTimer.secondsTotal', 10);

        $this->assertSame(1, PokerHand::query()->where('status', 'running')->count());
    }

    public function test_reidratacao_nao_cria_nova_mao_automaticamente_apos_mao_finalizada(): void
    {
        $userOne = User::factory()->create(['name' => 'Jogador 1']);
        $userTwo = User::factory()->create(['name' => 'Jogador 2']);
        $table = $this->createTable();

        $this->seatPlayer($table, $userOne, 1);
        $this->seatPlayer($table, $userTwo, 2);

        $this->actingAs($userOne)->get(route('poker.tables.show', $table))->assertOk();

        $hand = PokerHand::query()->latest('id')->firstOrFail();
        $state = $hand->state_payload;
        $state['isFinished'] = true;
        $state['street'] = 'showdown';
        $state['streetLabel'] = 'Showdown';
        $state['conclusion'] = [
            'isFinished' => true,
            'winner' => [
                'player' => 'player',
                'label' => 'Você',
                'handName' => 'Par',
            ],
            'message' => 'Mão finalizada para teste.',
        ];
        unset($state['turnTimer']);

        $hand->forceFill([
            'status' => 'finished',
            'street' => 'showdown',
            'state_payload' => $state,
            'finished_at' => now(),
        ])->save();

        $this->actingAs($userOne)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.isFinished', true)
            ->assertJsonPath('state.street', 'showdown')
            ->assertJsonPath('state.conclusion.message', 'Mão finalizada para teste.');

        $this->assertSame(1, PokerHand::query()->where('poker_table_id', $table->id)->count());
        $this->assertSame(0, PokerHand::query()->where('poker_table_id', $table->id)->where('status', 'running')->count());
    }

    public function test_nova_mao_nao_retorna_erro_quando_ja_existe_mao_em_andamento(): void
    {
        $userOne = User::factory()->create(['name' => 'Jogador 1']);
        $userTwo = User::factory()->create(['name' => 'Jogador 2']);
        $table = $this->createTable();

        $this->seatPlayer($table, $userOne, 1);
        $this->seatPlayer($table, $userTwo, 2);

        $this->actingAs($userOne)->get(route('poker.tables.show', $table))->assertOk();

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('message', 'Já existe uma mão em andamento nesta mesa.')
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.isWaitingForPlayers', false);

        $this->assertSame(1, PokerHand::query()->where('poker_table_id', $table->id)->count());
    }

    public function test_bot_conta_como_jogador_sentado_mesmo_quando_marcado_offline(): void
    {
        $humanUser = User::factory()->create(['name' => 'Jogador Humano']);
        $botUser = User::factory()->create(['name' => 'Bot Offline']);
        $table = $this->createTable();

        $this->seatPlayer($table, $humanUser, 1);
        $this->seatPlayer($table, $botUser, 2, true);

        PokerTablePlayer::query()
            ->where('user_id', $botUser->id)
            ->update(['status' => 'offline']);

        $this->actingAs($humanUser)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('message', 'Nova mão iniciada para todos os jogadores.')
            ->assertJsonPath('state.isWaitingForPlayers', false);
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

    private function seatPlayer(PokerTable $table, User $user, int $seatNumber, bool $isBot = false): void
    {
        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => $seatNumber,
            'status' => 'online',
            'is_bot' => $isBot,
            'bot_profile' => $isBot ? 'conservative' : null,
            'bot_difficulty' => $isBot ? 'normal' : null,
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
