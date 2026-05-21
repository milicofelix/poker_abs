<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerTournament;
use App\Models\Poker\PokerTournamentParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerTorneiosFaseDozeTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_de_torneios_abre_sem_torneios_cadastrados(): void
    {
        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.phase', '12.12.9')
                ->where('tournamentCenter.summary.total', 0)
                ->where('tournamentCenter.defaults.buyIn', 1000)
                ->where('tournamentCenter.defaults.smallBlind', 25)
                ->where('tournamentCenter.defaults.bigBlind', 50)
                ->where('tournamentCenter.defaults.payoutStructure.0.percent', 70)
                ->has('tournamentCenter.tournaments', 0)
            );
    }

    public function test_jogador_autenticado_cria_torneio_com_inscricoes_abertas(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);

        $this->actingAs($user)
            ->post(route('poker.tournaments.store'), [
                'name' => 'Torneio ABS Noite',
                'buy_in' => 1500,
                'starting_stack' => 7000,
                'max_players' => 18,
            ])
            ->assertRedirect(route('poker.tournaments.index'));

        $this->assertDatabaseHas('poker_tournaments', [
            'name' => 'Torneio ABS Noite',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => 1500,
            'starting_stack' => 7000,
            'max_players' => 18,
            'registered_players_count' => 0,
            'prize_pool' => 0,
            'current_blind_level' => 1,
            'small_blind' => 25,
            'big_blind' => 50,
            'blind_level_minutes' => 10,
            'paid_places_count' => 3,
        ]);
    }

    public function test_inscricao_em_torneio_debita_bankroll_e_cria_participante(): void
    {
        $user = User::factory()->create([
            'name' => 'Adriano Torneio',
            'poker_bankroll' => 10000,
        ]);

        $tournament = PokerTournament::query()->create([
            'name' => 'Sit & Go Teste',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => 1200,
            'starting_stack' => 6000,
            'max_players' => 9,
        ]);

        $this->actingAs($user)
            ->post(route('poker.tournaments.register', $tournament))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'poker_bankroll' => 8800,
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'status' => PokerTournamentParticipant::STATUS_REGISTERED,
            'starting_stack' => 6000,
            'current_stack' => 6000,
        ]);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'registered_players_count' => 1,
            'prize_pool' => 1200,
        ]);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $user->id,
            'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_BUY_IN,
            'amount' => -1200,
            'balance_before' => 10000,
            'balance_after' => 8800,
        ]);
    }

    public function test_jogador_nao_pode_se_inscrever_duas_vezes_no_mesmo_torneio(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 10000]);
        $tournament = PokerTournament::query()->create([
            'name' => 'Torneio duplicado',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => 1000,
            'starting_stack' => 5000,
            'max_players' => 9,
        ]);

        PokerTournamentParticipant::query()->create([
            'poker_tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'status' => PokerTournamentParticipant::STATUS_REGISTERED,
            'starting_stack' => 5000,
            'current_stack' => 5000,
            'registered_at' => now(),
        ]);

        $tournament->forceFill([
            'registered_players_count' => 1,
            'prize_pool' => 1000,
        ])->save();

        $this->actingAs($user)
            ->from(route('poker.tournaments.index'))
            ->post(route('poker.tournaments.register', $tournament))
            ->assertRedirect(route('poker.tournaments.index'))
            ->assertSessionHas('error', 'Você já está inscrito neste torneio.');

        $this->assertSame(1, PokerTournamentParticipant::query()->where('poker_tournament_id', $tournament->id)->count());
        $this->assertSame(10000, $user->fresh()->poker_bankroll);
    }

    public function test_central_lista_torneio_com_participante_inscrito(): void
    {
        $user = User::factory()->create([
            'name' => 'Jogador Inscrito',
            'poker_bankroll' => 9000,
        ]);
        $tournament = PokerTournament::query()->create([
            'name' => 'Torneio Listado',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => 1000,
            'starting_stack' => 5000,
            'max_players' => 6,
            'registered_players_count' => 1,
            'prize_pool' => 1000,
        ]);
        PokerTournamentParticipant::query()->create([
            'poker_tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'status' => PokerTournamentParticipant::STATUS_REGISTERED,
            'starting_stack' => 5000,
            'current_stack' => 5000,
            'registered_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.summary.total', 1)
                ->where('tournamentCenter.tournaments.0.name', 'Torneio Listado')
                ->where('tournamentCenter.tournaments.0.statusLabel', 'Inscrições abertas')
                ->where('tournamentCenter.tournaments.0.isRegistered', true)
                ->where('tournamentCenter.tournaments.0.canRegister', false)
                ->where('tournamentCenter.tournaments.0.participants.0.name', 'Jogador Inscrito')
            );
    }

    public function test_torneio_pode_ser_iniciado_com_dois_jogadores_inscritos(): void
    {
        $tournament = $this->createTournamentWithParticipants(2, 1000);

        $this->actingAs(User::factory()->create(['poker_bankroll' => 5000]))
            ->post(route('poker.tournaments.start', $tournament))
            ->assertRedirect();

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_RUNNING,
        ]);

        $this->assertSame(2, PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
            ->count());
    }

    public function test_eliminacao_registra_posicao_e_mantem_torneio_em_andamento(): void
    {
        $tournament = $this->createTournamentWithParticipants(3, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $participant = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->orderBy('id')
            ->firstOrFail();

        $this->actingAs(User::factory()->create(['poker_bankroll' => 5000]))
            ->post(route('poker.tournaments.participants.eliminate', [$tournament, $participant]))
            ->assertRedirect();

        $this->assertDatabaseHas('poker_tournament_participants', [
            'id' => $participant->id,
            'status' => PokerTournamentParticipant::STATUS_ELIMINATED,
            'finish_position' => 3,
            'current_stack' => 0,
        ]);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_RUNNING,
        ]);
    }

    public function test_ultima_eliminacao_finaliza_torneio_e_premia_campeao(): void
    {
        $tournament = $this->createTournamentWithParticipants(2, 1500);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $loser = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->orderBy('id')
            ->firstOrFail();

        $winner = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->whereKeyNot($loser->id)
            ->firstOrFail();

        $winnerBankrollBefore = (int) $winner->user->poker_bankroll;
        $loserBankrollBefore = (int) $loser->user->poker_bankroll;

        $this->actingAs(User::factory()->create(['poker_bankroll' => 5000]))
            ->post(route('poker.tournaments.participants.eliminate', [$tournament, $loser]))
            ->assertRedirect();

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_FINISHED,
            'prize_pool' => 3000,
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'id' => $winner->id,
            'status' => PokerTournamentParticipant::STATUS_WINNER,
            'finish_position' => 1,
            'prize_amount' => 2100,
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'id' => $loser->id,
            'status' => PokerTournamentParticipant::STATUS_ELIMINATED,
            'finish_position' => 2,
            'prize_amount' => 900,
        ]);

        $this->assertSame($winnerBankrollBefore + 2100, (int) $winner->user->fresh()->poker_bankroll);
        $this->assertSame($loserBankrollBefore + 900, (int) $loser->user->fresh()->poker_bankroll);
        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $winner->user_id,
            'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_PAYOUT,
            'amount' => 2100,
        ]);
        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $loser->user_id,
            'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_PAYOUT,
            'amount' => 900,
        ]);
    }

    public function test_central_exibe_ranking_do_torneio_finalizado(): void
    {
        $tournament = $this->createTournamentWithParticipants(2, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $loser = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->orderBy('id')
            ->firstOrFail();

        app(\App\Services\Poker\PokerTournamentService::class)->eliminate($tournament->fresh(), $loser);

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.tournaments.0.statusLabel', 'Finalizado')
                ->where('tournamentCenter.tournaments.0.ranking.0.finishPosition', 1)
                ->where('tournamentCenter.tournaments.0.ranking.0.statusLabel', 'Campeão')
            );
    }


    public function test_usuario_inscrito_pode_adicionar_bots_ate_completar_torneio(): void
    {
        $user = User::factory()->create([
            'name' => 'Adriano Torneio',
            'poker_bankroll' => 10000,
        ]);

        $tournament = PokerTournament::query()->create([
            'name' => 'Torneio com bots',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => 1000,
            'starting_stack' => 5000,
            'max_players' => 3,
        ]);

        $this->actingAs($user)
            ->post(route('poker.tournaments.register', $tournament))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('poker.tournaments.bots', $tournament->fresh()))
            ->assertRedirect()
            ->assertSessionHas('success', 'Bot inscrito no torneio.');

        $this->assertSame(2, PokerTournamentParticipant::query()->where('poker_tournament_id', $tournament->id)->count());
        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'registered_players_count' => 2,
            'prize_pool' => 2000,
        ]);

        $this->actingAs($user)
            ->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tournamentCenter.tournaments.0.canRegister', false)
                ->where('tournamentCenter.tournaments.0.canRegisterBot', true)
                ->where('tournamentCenter.tournaments.0.canStart', true)
            );
    }


    public function test_torneio_iniciado_configura_timer_de_blinds_progressivos(): void
    {
        $tournament = $this->createTournamentWithParticipants(2, 1000);

        $this->actingAs(User::factory()->create(['poker_bankroll' => 5000]))
            ->post(route('poker.tournaments.start', $tournament))
            ->assertRedirect();

        $tournament = $tournament->fresh();

        $this->assertSame(PokerTournament::STATUS_RUNNING, $tournament->status);
        $this->assertSame(1, (int) $tournament->current_blind_level);
        $this->assertSame(25, (int) $tournament->small_blind);
        $this->assertSame(50, (int) $tournament->big_blind);
        $this->assertNotNull($tournament->next_blind_at);
    }

    public function test_blinds_progressivos_podem_avancar_com_torneio_em_andamento(): void
    {
        $tournament = $this->createTournamentWithParticipants(3, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $this->actingAs(User::factory()->create(['poker_bankroll' => 5000]))
            ->post(route('poker.tournaments.blind-level', $tournament->fresh()))
            ->assertRedirect()
            ->assertSessionHas('success', 'Nível de blinds avançado.');

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'current_blind_level' => 2,
            'small_blind' => 50,
            'big_blind' => 100,
        ]);
    }

    public function test_central_exibe_estrutura_de_blinds_do_torneio(): void
    {
        $tournament = $this->createTournamentWithParticipants(2, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);
        app(\App\Services\Poker\PokerTournamentService::class)->advanceBlindLevel($tournament->fresh());

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.tournaments.0.blindStructure.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.blindStructure.currentLevel', 2)
                ->where('tournamentCenter.tournaments.0.blindStructure.smallBlind', 50)
                ->where('tournamentCenter.tournaments.0.blindStructure.bigBlind', 100)
                ->where('tournamentCenter.tournaments.0.canAdvanceBlind', true)
            );
    }


    public function test_torneio_com_seis_jogadores_distribui_premiacao_entre_top_tres(): void
    {
        $tournament = $this->createTournamentWithParticipants(6, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $participants = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->orderBy('id')
            ->get();

        foreach ($participants->take(5) as $participant) {
            app(\App\Services\Poker\PokerTournamentService::class)->eliminate($tournament->fresh(), $participant);
        }

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_FINISHED,
            'prize_pool' => 6000,
        ]);

        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'finish_position' => 1,
            'prize_amount' => 4200,
        ]);
        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'finish_position' => 2,
            'prize_amount' => 1200,
        ]);
        $this->assertDatabaseHas('poker_tournament_participants', [
            'poker_tournament_id' => $tournament->id,
            'finish_position' => 3,
            'prize_amount' => 600,
        ]);

        $this->assertSame(3, PokerBankrollTransaction::query()
            ->where('type', PokerBankrollTransaction::TYPE_TOURNAMENT_PAYOUT)
            ->count());
    }

    public function test_central_exibe_plano_de_premiacao_do_torneio(): void
    {
        $tournament = $this->createTournamentWithParticipants(6, 1000);

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.tournaments.0.payoutPlan.0.position', 1)
                ->where('tournamentCenter.tournaments.0.payoutPlan.0.percent', 70)
                ->where('tournamentCenter.tournaments.0.payoutPlan.0.amount', 4200)
                ->where('tournamentCenter.tournaments.0.payoutPlan.1.position', 2)
                ->where('tournamentCenter.tournaments.0.payoutPlan.1.amount', 1200)
                ->where('tournamentCenter.tournaments.0.payoutPlan.2.position', 3)
                ->where('tournamentCenter.tournaments.0.payoutPlan.2.amount', 600)
            );
    }


    public function test_torneio_sit_and_go_inicia_com_mesa_final_organizada(): void
    {
        $tournament = $this->createTournamentWithParticipants(6, 1000);

        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'status' => PokerTournament::STATUS_RUNNING,
            'is_final_table' => true,
        ]);

        $freshTournament = $tournament->fresh();

        $this->assertNotNull($freshTournament->final_table_started_at);
        $this->assertCount(6, $freshTournament->final_table_seat_map);
        $this->assertSame(1, $freshTournament->final_table_seat_map[0]['seat']);
    }

    public function test_central_exibe_mesa_final_com_assentos(): void
    {
        $tournament = $this->createTournamentWithParticipants(4, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.tournaments.0.finalTable.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.finalTable.enabled', true)
                ->where('tournamentCenter.tournaments.0.finalTable.maxPlayers', 9)
                ->has('tournamentCenter.tournaments.0.finalTable.seatMap', 4)
                ->where('tournamentCenter.tournaments.0.canPrepareFinalTable', false)
            );
    }


    public function test_reentrada_reativa_jogador_eliminado_e_incrementa_prize_pool(): void
    {
        $tournament = $this->createTournamentWithParticipants(3, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        /** @var PokerTournamentParticipant $participant */
        $participant = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->orderBy('id')
            ->firstOrFail();

        app(\App\Services\Poker\PokerTournamentService::class)->eliminate($tournament->fresh(), $participant);

        $this->actingAs($participant->user)
            ->post(route('poker.tournaments.participants.reentry', [$tournament->fresh(), $participant->fresh()]))
            ->assertRedirect();

        $this->assertDatabaseHas('poker_tournament_participants', [
            'id' => $participant->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'current_stack' => 5000,
            'finish_position' => null,
            'reentries_count' => 1,
        ]);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'prize_pool' => 4000,
        ]);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $participant->user_id,
            'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_REENTRY,
            'amount' => -1000,
        ]);
    }

    public function test_addon_aumenta_stack_do_jogador_ativo_uma_unica_vez(): void
    {
        $tournament = $this->createTournamentWithParticipants(2, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        /** @var PokerTournamentParticipant $participant */
        $participant = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->orderBy('id')
            ->firstOrFail();

        $this->actingAs($participant->user)
            ->post(route('poker.tournaments.participants.addon', [$tournament->fresh(), $participant]))
            ->assertRedirect();

        $this->assertDatabaseHas('poker_tournament_participants', [
            'id' => $participant->id,
            'status' => PokerTournamentParticipant::STATUS_ACTIVE,
            'current_stack' => 7500,
            'addons_count' => 1,
        ]);

        $this->assertDatabaseHas('poker_tournaments', [
            'id' => $tournament->id,
            'prize_pool' => 3000,
        ]);

        $this->assertDatabaseHas('poker_bankroll_transactions', [
            'user_id' => $participant->user_id,
            'type' => PokerBankrollTransaction::TYPE_TOURNAMENT_ADDON,
            'amount' => -1000,
        ]);
    }

    public function test_central_exibe_controles_de_reentrada_e_addon(): void
    {
        $tournament = $this->createTournamentWithParticipants(3, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        /** @var PokerTournamentParticipant $activeParticipant */
        $activeParticipant = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->where('status', PokerTournamentParticipant::STATUS_ACTIVE)
            ->firstOrFail();

        /** @var PokerTournamentParticipant $eliminatedParticipant */
        $eliminatedParticipant = PokerTournamentParticipant::query()
            ->where('poker_tournament_id', $tournament->id)
            ->where('id', '!=', $activeParticipant->id)
            ->firstOrFail();

        app(\App\Services\Poker\PokerTournamentService::class)->eliminate($tournament->fresh(), $eliminatedParticipant);

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.reentryAddon.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.reentryAddon.allowReentry', true)
                ->where('tournamentCenter.tournaments.0.reentryAddon.addonEnabled', true)
                ->where('tournamentCenter.tournaments.0.participants.0.canReenter', true)
            );
    }


    public function test_lobby_avancado_exibe_ocupacao_vagas_e_proximo_torneio_para_iniciar(): void
    {
        $tournament = PokerTournament::query()->create([
            'name' => 'Lobby Avançado ABS',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => 1000,
            'starting_stack' => 5000,
            'max_players' => 4,
            'registered_players_count' => 2,
            'prize_pool' => 2000,
        ]);

        for ($index = 1; $index <= 2; $index++) {
            $user = User::factory()->create([
                'name' => 'Jogador Lobby '.$index,
                'poker_bankroll' => 10000,
            ]);

            PokerTournamentParticipant::query()->create([
                'poker_tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => PokerTournamentParticipant::STATUS_REGISTERED,
                'starting_stack' => 5000,
                'current_stack' => 5000,
                'registered_at' => now()->subMinutes($index),
            ]);
        }

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.phase', '12.12.9')
                ->where('tournamentCenter.lobby.phase', '12.12.9')
                ->where('tournamentCenter.lobby.nextToStart.name', 'Lobby Avançado ABS')
                ->where('tournamentCenter.lobby.nextToStart.occupancyPercent', 50)
                ->where('tournamentCenter.tournaments.0.lobbySummary.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.lobbySummary.occupancyPercent', 50)
                ->where('tournamentCenter.tournaments.0.lobbySummary.availableSeats', 2)
                ->where('tournamentCenter.tournaments.0.lobbySummary.playersNeededToStart', 0)
                ->where('tournamentCenter.tournaments.0.lobbySummary.headline', 'Pronto para iniciar')
            );
    }


    public function test_torneio_persiste_snapshot_de_retomada_apos_inicio_e_avanco_de_blinds(): void
    {
        $tournament = $this->createTournamentWithParticipants(3, 1000);
        $service = app(\App\Services\Poker\PokerTournamentService::class);

        $service->start($tournament);
        $service->advanceBlindLevel($tournament->fresh());

        $tournament->refresh();

        $this->assertNotNull($tournament->resume_token);
        $this->assertNotNull($tournament->last_snapshot_at);
        $this->assertSame('12.12.9', $tournament->resume_snapshot['phase']);
        $this->assertSame(PokerTournament::STATUS_RUNNING, $tournament->resume_snapshot['status']);
        $this->assertSame(2, $tournament->resume_snapshot['currentBlindLevel']);
        $this->assertSame(50, $tournament->resume_snapshot['smallBlind']);
        $this->assertSame(100, $tournament->resume_snapshot['bigBlind']);
    }

    public function test_lobby_exibe_estado_de_retomada_do_torneio(): void
    {
        $tournament = $this->createTournamentWithParticipants(3, 1000);
        app(\App\Services\Poker\PokerTournamentService::class)->start($tournament);

        $this->get(route('poker.tournaments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Tournaments')
                ->where('tournamentCenter.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.resumeState.phase', '12.12.9')
                ->where('tournamentCenter.tournaments.0.resumeState.isRestorable', true)
                ->where('tournamentCenter.tournaments.0.resumeState.snapshot.status', PokerTournament::STATUS_RUNNING)
                ->where('tournamentCenter.tournaments.0.resumeState.snapshot.activePlayers', 3)
            );
    }

    private function createTournamentWithParticipants(int $participants, int $buyIn): PokerTournament
    {
        $tournament = PokerTournament::query()->create([
            'name' => 'Torneio Eliminatório',
            'status' => PokerTournament::STATUS_REGISTERING,
            'buy_in' => $buyIn,
            'starting_stack' => 5000,
            'max_players' => 9,
            'registered_players_count' => $participants,
            'prize_pool' => $participants * $buyIn,
        ]);

        for ($index = 1; $index <= $participants; $index++) {
            $user = User::factory()->create([
                'name' => 'Jogador '.$index,
                'poker_bankroll' => 10000,
            ]);

            PokerTournamentParticipant::query()->create([
                'poker_tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => PokerTournamentParticipant::STATUS_REGISTERED,
                'starting_stack' => 5000,
                'current_stack' => 5000,
                'registered_at' => now()->subMinutes($participants - $index),
            ]);
        }

        return $tournament->fresh(['participants.user']);
    }

}
