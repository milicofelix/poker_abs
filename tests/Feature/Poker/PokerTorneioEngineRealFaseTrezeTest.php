<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Models\User;
use App\Services\Poker\PokerTournamentRuntimeSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerTorneioEngineRealFaseTrezeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sincroniza_stacks_da_engine_real_com_participantes_do_torneio(): void
    {
        [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo] = $this->criarTorneioRodandoComMesaReal();

        app(PokerTournamentRuntimeSyncService::class)->syncAfterStateChange($table, [
            'isFinished' => false,
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    [
                        'tablePlayerId' => $seatOne->id,
                        'userId' => $playerOne->id,
                        'seatNumber' => 1,
                        'stack' => 4550,
                        'streetBet' => 150,
                    ],
                    [
                        'tablePlayerId' => $seatTwo->id,
                        'userId' => $playerTwo->id,
                        'seatNumber' => 2,
                        'stack' => 5200,
                        'streetBet' => 0,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerOne->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'current_stack' => 4700,
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerTwo->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'current_stack' => 5200,
        ]);
    }

    public function test_mao_finalizada_elimina_jogador_sem_stack_e_mantem_vencedor_ativo(): void
    {
        [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo] = $this->criarTorneioRodandoComMesaReal();

        app(PokerTournamentRuntimeSyncService::class)->syncAfterStateChange($table, [
            'isFinished' => true,
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    [
                        'tablePlayerId' => $seatOne->id,
                        'userId' => $playerOne->id,
                        'seatNumber' => 1,
                        'stack' => 0,
                        'streetBet' => 0,
                    ],
                    [
                        'tablePlayerId' => $seatTwo->id,
                        'userId' => $playerTwo->id,
                        'seatNumber' => 2,
                        'stack' => 10000,
                        'streetBet' => 0,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerOne->id,
            'status' => PokerTournamentParticipant::STATUS_ELIMINATED,
            'finish_position' => 2,
            'current_stack' => 0,
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerTwo->id,
            'status' => PokerTournamentParticipant::STATUS_WINNER,
            'finish_position' => 1,
        ]);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_FINISHED,
        ]);
    }

    public function test_estado_da_mesa_expoe_runtime_do_torneio_para_o_frontend(): void
    {
        [$tournament, $table, $playerOne] = $this->criarTorneioRodandoComMesaReal();

        $this->actingAs($playerOne)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('tournamentRuntime.phase', '13.2.3')
            ->assertJsonPath('tournamentRuntime.tournamentId', $tournament->id)
            ->assertJsonPath('tournamentRuntime.smallBlind', 25)
            ->assertJsonPath('tournamentRuntime.bigBlind', 50)
            ->assertJsonPath('tournamentRuntime.message', 'Mesa real sincronizada com stacks, blinds e eliminações do torneio.');
    }


    public function test_mao_finalizada_com_blind_vencido_avanca_nivel_e_prepara_proxima_mao(): void
    {
        [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo] = $this->criarTorneioRodandoComMesaReal();

        $tournament->forceFill(['next_blind_at' => now()->subMinute()])->save();

        app(PokerTournamentRuntimeSyncService::class)->syncAfterStateChange($table, [
            'isFinished' => true,
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    [
                        'tablePlayerId' => $seatOne->id,
                        'userId' => $playerOne->id,
                        'seatNumber' => 1,
                        'stack' => 4200,
                        'streetBet' => 0,
                    ],
                    [
                        'tablePlayerId' => $seatTwo->id,
                        'userId' => $playerTwo->id,
                        'seatNumber' => 2,
                        'stack' => 5800,
                        'streetBet' => 0,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_RUNNING,
            'current_blind_level' => 2,
            'small_blind' => 50,
            'big_blind' => 100,
        ]);

        $this->assertDatabaseHas('poker_tables', [
            'id' => $table->id,
            'small_blind' => 50,
            'big_blind' => 100,
        ]);
    }

    public function test_preparacao_da_proxima_mao_remove_eliminado_dos_assentos_reais(): void
    {
        [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo] = $this->criarTorneioRodandoComMesaReal();
        $playerThree = User::factory()->create(['name' => 'Jogador Três', 'poker_bankroll' => 10000]);

        PokerTournamentParticipant::query()->create([
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerThree->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'starting_stack' => 5000,
            'current_stack' => 5000,
            'registered_at' => now(),
        ]);

        $seatThree = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $playerThree->id,
            'nickname' => 'Jogador Três',
            'stack' => 5000,
            'seat_number' => 3,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        app(PokerTournamentRuntimeSyncService::class)->syncAfterStateChange($table, [
            'isFinished' => true,
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    [
                        'tablePlayerId' => $seatOne->id,
                        'userId' => $playerOne->id,
                        'seatNumber' => 1,
                        'stack' => 0,
                        'streetBet' => 0,
                    ],
                    [
                        'tablePlayerId' => $seatTwo->id,
                        'userId' => $playerTwo->id,
                        'seatNumber' => 2,
                        'stack' => 7000,
                        'streetBet' => 0,
                    ],
                    [
                        'tablePlayerId' => $seatThree->id,
                        'userId' => $playerThree->id,
                        'seatNumber' => 3,
                        'stack' => 8000,
                        'streetBet' => 0,
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerOne->id,
            'status' => PokerTournamentParticipant::STATUS_ELIMINATED,
            'finish_position' => 3,
        ]);

        $this->assertDatabaseHas('poker_table_players', [
            'id' => $seatOne->id,
            'stack' => 0,
            'seat_number' => null,
            'status' => 'offline',
        ]);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'user_id' => $playerThree->id,
            'stack' => 8000,
            'seat_number' => 1,
            'status' => 'online',
        ]);
    }


    public function test_botao_iniciar_nova_mao_reencaixa_torneio_a_partir_da_mao_finalizada(): void
    {
        [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo] = $this->criarTorneioRodandoComMesaReal();
        $playerThree = User::factory()->create(['name' => 'Bot Torneio 3', 'email' => 'bot-torneio-3@pokerabs.local', 'poker_bankroll' => 10000]);

        PokerTournamentParticipant::query()->create([
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerThree->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'starting_stack' => 5000,
            'current_stack' => 5000,
            'registered_at' => now(),
        ]);

        $seatThree = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $playerThree->id,
            'nickname' => 'Bot Torneio 3',
            'is_bot' => true,
            'stack' => 5000,
            'seat_number' => 3,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        $finishedState = [
            'street' => 'showdown',
            'isFinished' => true,
            'pot' => 1000,
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    [
                        'tablePlayerId' => $seatOne->id,
                        'userId' => $playerOne->id,
                        'seatNumber' => 1,
                        'stack' => 0,
                        'streetBet' => 0,
                    ],
                    [
                        'tablePlayerId' => $seatTwo->id,
                        'userId' => $playerTwo->id,
                        'seatNumber' => 2,
                        'stack' => 3500,
                        'streetBet' => 0,
                    ],
                    [
                        'tablePlayerId' => $seatThree->id,
                        'userId' => $playerThree->id,
                        'seatNumber' => 3,
                        'stack' => 6500,
                        'streetBet' => 0,
                    ],
                ],
            ],
            'persistence' => [
                'multiSeat' => true,
                'tableId' => $table->id,
                'handId' => 999999,
                'loggedActions' => 0,
                'syncVersion' => 1,
            ],
        ];

        $hand = $table->hands()->create([
            'code' => 'mao-finalizada-teste-reencaixe',
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => 1000,
            'current_bet' => 0,
            'dealer_position' => 1,
            'state_payload' => $finishedState,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        $finishedState['persistence']['handId'] = $hand->id;
        $hand->forceFill(['state_payload' => $finishedState])->save();

        $this->actingAs($playerTwo)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonCount(2, 'state.multiSeat.players');

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerOne->id,
            'status' => PokerTournamentParticipant::STATUS_ELIMINATED,
            'current_stack' => 0,
        ]);

        $this->assertDatabaseHas('poker_table_players', [
            'id' => $seatOne->id,
            'stack' => 0,
            'seat_number' => null,
            'status' => 'offline',
        ]);
    }


    public function test_botao_iniciar_nova_mao_no_heads_up_final_fecha_snapshot_terminal_antigo(): void
    {
        [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo] = $this->criarTorneioRodandoComMesaReal();
        $playerThree = User::factory()->create(['name' => 'Bot Torneio 3', 'email' => 'bot-torneio-3@pokerabs.local', 'poker_bankroll' => 10000]);
        $playerFour = User::factory()->create(['name' => 'Bot Torneio 4', 'email' => 'bot-torneio-4@pokerabs.local', 'poker_bankroll' => 10000]);

        foreach ([[$playerThree, 50], [$playerFour, 0]] as [$user, $stack]) {
            PokerTournamentParticipant::query()->create([
                'poker_tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => $stack > 0 ? PokerTournamentParticipant::STATUS_ACTIVE : PokerTournamentParticipant::STATUS_ELIMINATED,
                'starting_stack' => 5000,
                'current_stack' => $stack,
                'registered_at' => now(),
                'finish_position' => $stack > 0 ? null : 3,
                'eliminated_at' => $stack > 0 ? null : now(),
            ]);
        }

        $seatThree = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $playerThree->id,
            'nickname' => 'Bot Torneio 3',
            'is_bot' => true,
            'stack' => 50,
            'seat_number' => 2,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        $seatFour = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $playerFour->id,
            'nickname' => 'Bot Torneio 4',
            'is_bot' => true,
            'stack' => 0,
            'seat_number' => 3,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        $seatOne->forceFill(['stack' => 1125, 'seat_number' => 1, 'status' => 'online', 'left_at' => null])->save();
        $seatTwo->forceFill(['stack' => 0, 'seat_number' => null, 'status' => 'offline', 'left_at' => now()])->save();

        PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->where('user_id', $playerOne->id)
            ->update(['current_stack' => 1125]);

        PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->where('user_id', $playerTwo->id)
            ->update(['status' => PokerTournamentParticipant::STATUS_ELIMINATED, 'current_stack' => 0, 'finish_position' => 4, 'eliminated_at' => now()]);

        $terminalState = [
            'street' => 'showdown',
            'isFinished' => true,
            'pot' => 1200,
            'multiSeat' => [
                'enabled' => true,
                'players' => [
                    ['tablePlayerId' => $seatOne->id, 'userId' => $playerOne->id, 'seatNumber' => 1, 'stack' => 1125, 'streetBet' => 0],
                    ['tablePlayerId' => $seatThree->id, 'userId' => $playerThree->id, 'seatNumber' => 2, 'stack' => 50, 'streetBet' => 0],
                    ['tablePlayerId' => $seatFour->id, 'userId' => $playerFour->id, 'seatNumber' => 3, 'stack' => 0, 'streetBet' => 0],
                ],
            ],
            'persistence' => ['multiSeat' => true, 'tableId' => $table->id, 'handId' => 999999, 'loggedActions' => 0, 'syncVersion' => 1],
        ];

        $hand = $table->hands()->create([
            'code' => 'mao-terminal-running-heads-up-torneio',
            'status' => 'running',
            'street' => 'showdown',
            'pot' => 1200,
            'current_bet' => 0,
            'dealer_position' => 1,
            'state_payload' => $terminalState,
            'started_at' => now()->subMinutes(2),
            'finished_at' => null,
        ]);

        $terminalState['persistence']['handId'] = $hand->id;
        $hand->forceFill(['state_payload' => $terminalState])->save();

        $this->actingAs($playerOne)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonCount(2, 'state.multiSeat.players');

        $this->assertDatabaseHas('poker_hands', [
            'id' => $hand->id,
            'status' => 'finished',
        ]);

        $this->actingAs($playerOne)
            ->getJson(route('poker.tables.state', $table))
            ->assertOk()
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonCount(2, 'state.multiSeat.players');
    }

    private function criarTorneioRodandoComMesaReal(): array
    {
        $playerOne = User::factory()->create(['name' => 'Jogador Um', 'poker_bankroll' => 10000]);
        $playerTwo = User::factory()->create(['name' => 'Jogador Dois', 'poker_bankroll' => 10000]);

        $table = PokerTable::query()->create([
            'name' => 'Mesa real do torneio teste',
            'status' => 'playing',
            'small_blind' => 25,
            'big_blind' => 50,
            'max_players' => 9,
        ]);

        $tournament = PokerTournament::query()->create([
            'poker_table_id' => $table->id,
            'name' => 'Torneio engine real teste',
            'status' => PokerTournament::STATUS_RUNNING,
            'buy_in' => 1000,
            'starting_stack' => 5000,
            'max_players' => 9,
            'registered_players_count' => 2,
            'prize_pool' => 2000,
            'current_blind_level' => 1,
            'small_blind' => 25,
            'big_blind' => 50,
            'blind_level_minutes' => 10,
            'next_blind_at' => now()->addMinutes(10),
            'payout_structure' => PokerTournament::DEFAULT_PAYOUT_STRUCTURE,
            'paid_places_count' => 3,
        ]);

        PokerTournamentParticipant::query()->create([
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerOne->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'starting_stack' => 5000,
            'current_stack' => 5000,
            'registered_at' => now(),
        ]);

        PokerTournamentParticipant::query()->create([
            'poker_tournament_id' => $tournament->id,
            'user_id' => $playerTwo->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'starting_stack' => 5000,
            'current_stack' => 5000,
            'registered_at' => now(),
        ]);

        $seatOne = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $playerOne->id,
            'nickname' => 'Jogador Um',
            'stack' => 5000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        $seatTwo = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $playerTwo->id,
            'nickname' => 'Jogador Dois',
            'stack' => 5000,
            'seat_number' => 2,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        return [$tournament, $table, $playerOne, $playerTwo, $seatOne, $seatTwo];
    }
}
