<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerBotsMesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_da_mesa_envia_configuracao_para_adicionar_bots(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->get(route('poker.tables.show', $table))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('table.botUrl', route('poker.tables.bots', $table))
                ->where('table.botProfiles.0.key', 'conservative')
                ->where('table.botProfiles.1.key', 'aggressive')
                ->where('table.botProfiles.2.key', 'tag')
                ->where('table.botProfiles.3.key', 'lag')
                ->where('table.botProfiles.4.key', 'nit')
                ->where('table.botProfiles.5.key', 'calling_station')
                ->where('table.botProfiles.6.key', 'maniac')
                ->where('table.botDifficulties.1', 'normal')
                ->where('table.botDifficultyOptions.0.key', 'easy')
                ->where('table.botDifficultyOptions.1.label', 'Normal')
                ->where('table.botDifficultyOptions.2.key', 'hard'));
    }

    public function test_usuario_autenticado_consegue_adicionar_bot_em_assento_livre(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'conservative',
                'difficulty' => 'normal',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Bot Conservador entrou na mesa.')
            ->assertJsonPath('bot.isBot', true)
            ->assertJsonPath('bot.botProfile', 'conservative')
            ->assertJsonPath('bot.botDifficulty', 'normal')
            ->assertJsonPath('bot.botDifficultyLabel', 'Normal')
            ->assertJsonPath('bot.seatNumber', 2)
            ->assertJsonPath('seatSlots.1.status', 'occupied')
            ->assertJsonPath('seatSlots.1.player.isBot', true);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'nickname' => 'Bot Conservador',
            'seat_number' => 2,
            'is_bot' => true,
            'bot_profile' => 'conservative',
            'bot_difficulty' => 'normal',
        ]);
    }


    public function test_usuario_consegue_adicionar_bot_com_personalidade_avancada(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'maniac',
                'difficulty' => 'hard',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Bot Maniac entrou na mesa.')
            ->assertJsonPath('bot.nickname', 'Bot Maniac')
            ->assertJsonPath('bot.botProfile', 'maniac')
            ->assertJsonPath('bot.botDifficulty', 'hard')
            ->assertJsonPath('seatSlots.1.player.botProfile', 'maniac');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'seat_number' => 2,
            'is_bot' => true,
            'bot_profile' => 'maniac',
            'bot_difficulty' => 'hard',
            'nickname' => 'Bot Maniac',
        ]);
    }

    public function test_usuario_consegue_adicionar_bot_com_dificuldade_dificil(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);
        $table = $this->createTable();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'stack' => 1000,
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'aggressive',
                'difficulty' => 'hard',
            ])
            ->assertOk()
            ->assertJsonPath('bot.botProfile', 'aggressive')
            ->assertJsonPath('bot.botDifficulty', 'hard')
            ->assertJsonPath('bot.botDifficultyLabel', 'Difícil')
            ->assertJsonPath('seatSlots.1.player.botDifficultyLabel', 'Difícil');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'seat_number' => 2,
            'is_bot' => true,
            'bot_profile' => 'aggressive',
            'bot_difficulty' => 'hard',
        ]);
    }

    public function test_dificuldade_de_bot_invalida_e_rejeitada(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'conservative',
                'difficulty' => 'impossivel',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('difficulty');
    }

    public function test_nao_adiciona_bot_quando_mesa_esta_sem_assento_livre(): void
    {
        $userOne = User::factory()->create(['name' => 'Jogador 1']);
        $userTwo = User::factory()->create(['name' => 'Jogador 2']);
        $table = $this->createTable();

        $this->seatPlayer($table, $userOne, 1);
        $this->seatPlayer($table, $userTwo, 2);

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'aggressive',
                'difficulty' => 'normal',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Não existe assento livre para adicionar um bot nesta mesa.');
    }

    public function test_perfil_de_bot_invalido_e_rejeitado(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'maluco',
                'difficulty' => 'normal',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile');
    }


    public function test_bot_age_automaticamente_quando_a_vez_chega_nele(): void
    {
        [$table, $user] = $this->createTableWithHumanAndBot();

        $this->actingAs($user)
            ->postJson(route('poker.tables.actions', $table), [
                'action' => 'call',
                'raiseAmount' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('state.street', 'flop')
            ->assertJsonPath('state.currentTurn.canonicalActor', 'player')
            ->assertJsonPath('state.currentTurn.isCurrentUserTurn', true)
            ->assertJsonPath('state.lastAction.actor', 'opponent')
            ->assertJsonPath('state.lastAction.isBot', true)
            ->assertJsonPath('state.botDecision.processed', true)
            ->assertJsonPath('state.botDecision.actor', 'opponent')
            ->assertJsonPath('state.botDecision.handStrength.label', 'mão jogável')
            ->assertJsonPath('state.botDecision.stats.totalDecisions', 1)
            ->assertJsonPath('state.botDecision.context.potPressure', 'small_pot')
            ->assertJsonPath('state.botDecision.memory.opponentModel', 'unknown')
            ->assertJsonPath('state.botDecision.context.stackPressure', 'deep_stack')
            ->assertJsonPath('state.botDecision.handStrength.probability.callRecommendation', 'neutral')
            ->assertJsonPath('state.botDecision.handStrength.probability.outs', 0)
            ->assertJsonPath('state.lastAction.botStats.totalDecisions', 1)
            ->assertJsonPath('state.lastAction.botContext.potPressure', 'small_pot')
            ->assertJsonPath('state.lastAction.botMemory.opponentModel', 'unknown');

        $this->assertDatabaseHas('poker_bot_decision_logs', [
            'poker_table_id' => $table->id,
            'actor' => 'opponent',
            'profile' => 'conservative',
            'difficulty' => 'normal',
            'label' => 'mão jogável',
        ]);

        $this->assertDatabaseCount('poker_bot_decision_logs', 1);
        $log = \App\Models\Poker\PokerBotDecisionLog::query()->firstOrFail();
        $this->assertIsArray($log->context);
        $this->assertArrayHasKey('probability', $log->context);
        $this->assertSame(0, $log->context['outs']);
    }


    public function test_mesa_pode_iniciar_simulacao_com_dois_bots_sem_jogador_humano_sentado(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'conservative',
                'difficulty' => 'normal',
            ])
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', true);

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'aggressive',
                'difficulty' => 'hard',
            ])
            ->assertOk()
            ->assertJsonPath('state.isWaitingForPlayers', false)
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('players.0.isBot', true)
            ->assertJsonPath('players.1.isBot', true);

        $this->assertDatabaseHas('poker_hands', [
            'poker_table_id' => $table->id,
            'status' => 'running',
        ]);
    }


    public function test_timeout_de_simulacao_bot_vs_bot_executa_jogada_do_bot_em_vez_de_fold_automatico(): void
    {
        Carbon::setTestNow('2026-05-16 00:00:00');

        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'conservative',
            'difficulty' => 'normal',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'tag',
            'difficulty' => 'normal',
        ])->assertOk();

        Carbon::setTestNow('2026-05-16 00:00:31');

        $this->actingAs($user)
            ->postJson(route('poker.tables.timeout', $table))
            ->assertOk()
            ->assertJsonPath('processed', true)
            ->assertJsonPath('action', 'bot')
            ->assertJsonPath('state.turnTimeout.label', 'Jogada automática do bot')
            ->assertJsonPath('state.lastAction.isBot', true);

        $this->assertDatabaseHas('poker_hands', [
            'poker_table_id' => $table->id,
            'status' => 'running',
        ]);

        Carbon::setTestNow();
    }


    public function test_simulacao_bot_vs_bot_usa_timer_de_dez_segundos(): void
    {
        Carbon::setTestNow('2026-05-16 00:00:00');

        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'conservative',
            'difficulty' => 'normal',
        ])->assertOk();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'tag',
                'difficulty' => 'normal',
            ])
            ->assertOk()
            ->assertJsonPath('state.botVsBotSimulation', true)
            ->assertJsonPath('state.turnTimer.secondsTotal', 10);

        Carbon::setTestNow();
    }

    public function test_simulacao_bot_vs_bot_nao_fica_em_loop_infinito_de_raise(): void
    {
        Carbon::setTestNow('2026-05-16 00:00:00');

        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'aggressive',
            'difficulty' => 'hard',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'tag',
            'difficulty' => 'hard',
        ])->assertOk();

        $hand = $table->hands()->where('status', 'running')->firstOrFail();
        $state = $hand->state_payload;
        $state['botVsBotSimulation'] = true;
        $state['currentBet'] = 60;
        $state['playerStreetBet'] = 60;
        $state['opponentStreetBet'] = 20;
        $state['amountToCall'] = 40;
        $state['currentTurn'] = [
            'actor' => 'opponent',
            'actedThisStreet' => [
                'player' => true,
                'opponent' => false,
            ],
            'label' => 'Vez do oponente',
        ];
        $state['actionHistory'] = [
            [
                'street' => 'Pré-flop',
                'action' => 'Raise',
                'amount' => 50,
                'message' => 'Bot agressivo aumentou.',
                'pot' => 80,
                'actor' => 'player',
            ],
        ];
        $state['turnTimer'] = [
            'secondsTotal' => 10,
            'startedAt' => '2026-05-16T00:00:00-03:00',
            'expiresAt' => '2026-05-16T00:00:10-03:00',
            'serverNow' => '2026-05-16T00:00:00-03:00',
            'isExpired' => false,
            'label' => 'Tempo da jogada',
        ];

        $hand->forceFill(['state_payload' => $state])->save();

        Carbon::setTestNow('2026-05-16 00:00:11');

        $response = $this->actingAs($user)
            ->postJson(route('poker.tables.timeout', $table));

        $response
            ->assertOk()
            ->assertJsonPath('processed', true)
            ->assertJsonPath('action', 'bot')
            ->assertJsonPath('state.botVsBotSimulation', true);

        $lastActionType = $response->json('state.lastAction.type');

        $this->assertContains($lastActionType, ['call', 'fold']);
        $this->assertNotSame('raise', $lastActionType);

        if ($response->json('state.isFinished') === true) {
            $this->assertNull($response->json('state.turnTimer'));
        } else {
            $this->assertSame(10, $response->json('state.turnTimer.secondsTotal'));
        }

        Carbon::setTestNow();
    }

    public function test_usuario_pode_trocar_bot_adversario_apos_mao_finalizada(): void
    {
        [$table, $user] = $this->createTableWithHumanAndBot();

        $table->hands()->firstOrFail()->forceFill([
            'status' => 'finished',
            'state_payload' => [
                ...$table->hands()->firstOrFail()->state_payload,
                'isFinished' => true,
                'conclusion' => ['isFinished' => true],
            ],
            'finished_at' => now(),
        ])->save();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'maniac',
                'difficulty' => 'hard',
                'replace_bot' => true,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Adversário trocado para Bot Maniac.')
            ->assertJsonPath('bot.seatNumber', 2)
            ->assertJsonPath('bot.botProfile', 'maniac')
            ->assertJsonPath('bot.botDifficulty', 'hard');

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'nickname' => 'Bot Conservador',
            'seat_number' => null,
            'status' => 'offline',
            'is_bot' => true,
        ]);

        $this->assertDatabaseHas('poker_table_players', [
            'poker_table_id' => $table->id,
            'nickname' => 'Bot Maniac',
            'seat_number' => 2,
            'status' => 'online',
            'is_bot' => true,
            'bot_profile' => 'maniac',
            'bot_difficulty' => 'hard',
        ]);
    }

    public function test_nao_troca_bot_adversario_com_mao_em_andamento(): void
    {
        [$table, $user] = $this->createTableWithHumanAndBot();

        $this->actingAs($user)
            ->postJson(route('poker.tables.bots', $table), [
                'profile' => 'maniac',
                'difficulty' => 'hard',
                'replace_bot' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Só é possível trocar o adversário após a mão finalizar.');
    }


    public function test_nova_mao_bot_vs_bot_nao_processa_a_mao_inteira_no_request_inicial(): void
    {
        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'aggressive',
            'difficulty' => 'normal',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'tag',
            'difficulty' => 'normal',
        ])->assertOk();

        $table->hands()->update([
            'status' => 'finished',
            'finished_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.botVsBotSimulation', true)
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.street', 'pre_flop')
            ->assertJsonPath('state.turnTimer.secondsTotal', 10);
    }

    public function test_timeout_em_bot_vs_bot_processa_bot_e_nao_aplica_fold_automatico_do_jogador(): void
    {
        Carbon::setTestNow('2026-05-16 10:00:00');

        $user = User::factory()->create();
        $table = $this->createTable();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'aggressive',
            'difficulty' => 'normal',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('poker.tables.bots', $table), [
            'profile' => 'tag',
            'difficulty' => 'normal',
        ])->assertOk();

        $hand = $table->hands()->where('status', 'running')->firstOrFail();
        $state = $hand->state_payload;
        $state['botVsBotSimulation'] = true;
        $state['turnTimer'] = [
            'secondsTotal' => 10,
            'startedAt' => '2026-05-16T10:00:00-03:00',
            'expiresAt' => '2026-05-16T10:00:10-03:00',
            'serverNow' => '2026-05-16T10:00:00-03:00',
            'isExpired' => false,
            'label' => 'Tempo da jogada',
        ];
        $hand->forceFill(['state_payload' => $state])->save();

        Carbon::setTestNow('2026-05-16 10:00:11');

        $this->actingAs($user)
            ->postJson(route('poker.tables.timeout', $table))
            ->assertOk()
            ->assertJsonPath('processed', true)
            ->assertJsonPath('action', 'bot')
            ->assertJsonPath('state.botVsBotSimulation', true)
            ->assertJsonMissingPath('state.conclusion.winner.handName');

        Carbon::setTestNow();
    }

    private function createTable(): PokerTable
    {
        return PokerTable::query()->create([
            'name' => 'Mesa com bots',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);
    }

    /**
     * @return array{0: PokerTable, 1: User}
     */
    private function createTableWithHumanAndBot(): array
    {
        $user = User::factory()->create(['name' => 'Jogador Humano']);
        $botUser = User::factory()->create(['name' => 'Bot Conservador']);
        $table = $this->createTable();

        $this->seatPlayer($table, $user, 1);

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
            'joined_at' => now()->addSecond(),
            'last_seen_at' => now(),
        ]);

        $canonicalPlayer = $table->players()->create([
            'seat' => 1,
            'name' => 'Jogador Humano',
            'type' => 'real_user',
            'stack' => 990,
        ]);

        $canonicalOpponent = $table->players()->create([
            'seat' => 2,
            'name' => 'Bot Conservador',
            'type' => 'simple_bot',
            'stack' => 980,
        ]);

        $playerSeat = $table->seats()->create([
            'poker_player_id' => $canonicalPlayer->id,
            'seat_number' => 1,
            'status' => 'occupied',
            'role' => 'real_user',
            'stack_snapshot' => 990,
            'is_dealer' => true,
            'is_small_blind' => true,
            'is_big_blind' => false,
        ]);

        $opponentSeat = $table->seats()->create([
            'poker_player_id' => $canonicalOpponent->id,
            'seat_number' => 2,
            'status' => 'occupied',
            'role' => 'simple_bot',
            'stack_snapshot' => 980,
            'is_dealer' => false,
            'is_small_blind' => false,
            'is_big_blind' => true,
        ]);

        $hand = $table->hands()->create([
            'code' => (string) Str::uuid(),
            'status' => 'running',
            'street' => 'pre_flop',
            'pot' => 30,
            'current_bet' => 20,
            'dealer_position' => 1,
            'state_payload' => [],
            'started_at' => now(),
        ]);

        $hand->forceFill([
            'state_payload' => [
                'street' => 'pre_flop',
                'streetLabel' => 'Pré-flop',
                'pot' => 30,
                'playerStack' => 990,
                'opponentStack' => 980,
                'currentBet' => 20,
                'playerStreetBet' => 10,
                'opponentStreetBet' => 20,
                'amountToCall' => 10,
                'minimumRaise' => 20,
                'minimumRaiseTo' => 40,
                'maximumRaiseTo' => 1000,
                'smallBlind' => 10,
                'bigBlind' => 20,
                'dealerPosition' => 1,
                'canCheck' => false,
                'canCall' => true,
                'canRaise' => true,
                'isFinished' => false,
                'playerCards' => [
                    ['rank' => 'A', 'suit' => 'spades', 'label' => 'A♠'],
                    ['rank' => 'K', 'suit' => 'spades', 'label' => 'K♠'],
                ],
                'opponentCards' => [
                    ['rank' => '7', 'suit' => 'diamonds', 'label' => '7♦'],
                    ['rank' => '7', 'suit' => 'clubs', 'label' => '7♣'],
                ],
                'communityCards' => [],
                'bestHand' => ['name' => 'Carta alta', 'rank' => 1, 'kickers' => [], 'cards' => []],
                'opponentBestHand' => ['name' => 'Par', 'rank' => 2, 'kickers' => [], 'cards' => []],
                'actionHistory' => [],
                'conclusion' => null,
                'currentTurn' => [
                    'actor' => 'player',
                    'actedThisStreet' => [
                        'player' => false,
                        'opponent' => false,
                    ],
                    'label' => 'Vez do jogador',
                ],
                'persistence' => [
                    'tableId' => $table->id,
                    'handId' => $hand->id,
                    'playerId' => $canonicalPlayer->id,
                    'opponentId' => $canonicalOpponent->id,
                    'playerSeatId' => $playerSeat->id,
                    'opponentSeatId' => $opponentSeat->id,
                    'loggedActions' => 0,
                    'syncVersion' => 1,
                ],
            ],
        ])->save();

        return [$table, $user];
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
