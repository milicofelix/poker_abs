<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerPerfilEstatisticasAvancadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_exibe_estatisticas_avancadas_sem_historico(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 1000]);

        $response = $this->get(route('poker.players.show', $user));

        $response->assertOk();

        $advanced = $response->viewData('page')['props']['profile']['advancedStatistics'];

        $this->assertSame('12.11.9', $advanced['phase']);
        $this->assertSame('30d', $advanced['period']);
        $this->assertSame(0, $advanced['summary']['handsPlayed']);
        $this->assertNull($advanced['summary']['winRate']);
        $this->assertNull($advanced['summary']['roi']);
    }

    public function test_perfil_calcula_winrate_fold_rate_all_in_rate_e_roi(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 1200]);
        $table = $this->createTable();
        $tablePlayer = $this->createTablePlayer($table, $user);
        $pokerPlayer = $this->createPokerPlayer($table, $user);

        $wonHand = $this->createFinishedHand($table, 200, now()->subDays(3));
        $lostHand = $this->createFinishedHand($table, 100, now()->subDays(2));

        $this->createTransaction($user, $table, $tablePlayer, $wonHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(3));
        $this->createTransaction($user, $table, $tablePlayer, $wonHand, PokerBankrollTransaction::TYPE_PAYOUT, 180, now()->subDays(3));
        $this->createTransaction($user, $table, $tablePlayer, $lostHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(2));

        $this->createAction($wonHand, $tablePlayer, $pokerPlayer, 'Call', 20, false);
        $this->createAction($wonHand, $tablePlayer, $pokerPlayer, 'All-in', 80, true);
        $this->createAction($lostHand, $tablePlayer, $pokerPlayer, 'Fold', 0, false);
        $this->createAction($lostHand, $tablePlayer, $pokerPlayer, 'Call', 20, false);

        $response = $this->get(route('poker.players.show', ['user' => $user, 'advanced_period' => '30d']));

        $response->assertOk();

        $summary = $response->viewData('page')['props']['profile']['advancedStatistics']['summary'];

        $this->assertSame(2, $summary['handsPlayed']);
        $this->assertSame(1, $summary['handsWon']);
        $this->assertSame(50.0, $summary['winRate']);
        $this->assertSame(25.0, $summary['foldRate']);
        $this->assertSame(25.0, $summary['allInRate']);
        $this->assertSame(200, $summary['invested']);
        $this->assertSame(180, $summary['returned']);
        $this->assertSame(-20, $summary['netProfit']);
        $this->assertSame(-10.0, $summary['roi']);
    }

    public function test_perfil_filtra_estatisticas_avancadas_por_periodo(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 1500]);
        $table = $this->createTable();
        $tablePlayer = $this->createTablePlayer($table, $user);
        $pokerPlayer = $this->createPokerPlayer($table, $user);

        $recentHand = $this->createFinishedHand($table, 120, now()->subDays(4));
        $oldHand = $this->createFinishedHand($table, 300, now()->subDays(40));

        $this->createTransaction($user, $table, $tablePlayer, $recentHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(4));
        $this->createTransaction($user, $table, $tablePlayer, $recentHand, PokerBankrollTransaction::TYPE_PAYOUT, 120, now()->subDays(4));
        $this->createTransaction($user, $table, $tablePlayer, $oldHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(40));
        $this->createTransaction($user, $table, $tablePlayer, $oldHand, PokerBankrollTransaction::TYPE_PAYOUT, 300, now()->subDays(40));

        $this->createAction($recentHand, $tablePlayer, $pokerPlayer, 'Call', 20, false, now()->subDays(4));
        $this->createAction($oldHand, $tablePlayer, $pokerPlayer, 'All-in', 100, true, now()->subDays(40));

        $response = $this->get(route('poker.players.show', ['user' => $user, 'advanced_period' => '7d']));

        $response->assertOk();

        $advanced = $response->viewData('page')['props']['profile']['advancedStatistics'];

        $this->assertSame('7d', $advanced['period']);
        $this->assertSame(1, $advanced['summary']['handsPlayed']);
        $this->assertSame(1, $advanced['summary']['handsWon']);
        $this->assertSame(100.0, $advanced['summary']['winRate']);
        $this->assertSame(20, $advanced['summary']['netProfit']);
    }


    public function test_perfil_monta_series_para_graficos_de_desempenho(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 1600]);
        $table = $this->createTable();
        $tablePlayer = $this->createTablePlayer($table, $user);
        $pokerPlayer = $this->createPokerPlayer($table, $user);

        $firstHand = $this->createFinishedHand($table, 200, now()->subDays(5));
        $secondHand = $this->createFinishedHand($table, 300, now()->subDays(2));

        $this->createTransaction($user, $table, $tablePlayer, $firstHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(5));
        $this->createTransaction($user, $table, $tablePlayer, $firstHand, PokerBankrollTransaction::TYPE_PAYOUT, 150, now()->subDays(5));
        $this->createTransaction($user, $table, $tablePlayer, $secondHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(2));

        $this->createAction($firstHand, $tablePlayer, $pokerPlayer, 'Call', 20, false, now()->subDays(5));
        $this->createAction($secondHand, $tablePlayer, $pokerPlayer, 'Fold', 0, false, now()->subDays(2));

        $response = $this->get(route('poker.players.show', ['user' => $user, 'advanced_period' => '7d']));

        $response->assertOk();

        $charts = $response->viewData('page')['props']['profile']['advancedStatistics']['charts'];

        $this->assertFalse($charts['empty']);
        $this->assertCount(2, $charts['profitByPeriod']);
        $this->assertSame(50, $charts['profitByPeriod'][0]['profit']);
        $this->assertSame(50, $charts['profitByPeriod'][0]['cumulativeProfit']);
        $this->assertSame(-100, $charts['profitByPeriod'][1]['profit']);
        $this->assertSame(-50, $charts['profitByPeriod'][1]['cumulativeProfit']);
        $this->assertSame(100, $charts['bankrollEvolution'][0]['invested']);
        $this->assertSame(150, $charts['bankrollEvolution'][0]['returned']);
        $this->assertSame(1, $charts['handsPerformance'][0]['played']);
        $this->assertSame(1, $charts['handsPerformance'][0]['won']);
        $this->assertSame(1, $charts['handsPerformance'][1]['played']);
        $this->assertSame(0, $charts['handsPerformance'][1]['won']);
        $this->assertSame('Call', $charts['actionDistribution'][1]['label']);
        $this->assertSame(1, $charts['actionDistribution'][1]['count']);
        $this->assertSame('Fold', $charts['actionDistribution'][4]['label']);
        $this->assertSame(1, $charts['actionDistribution'][4]['count']);
        $this->assertSame(100.0, $charts['financialEfficiency']['averageInvestedPerHand']);
        $this->assertSame(75.0, $charts['financialEfficiency']['averageReturnedPerHand']);
        $this->assertSame(-25.0, $charts['financialEfficiency']['averageProfitPerHand']);
    }


    public function test_perfil_compara_periodo_atual_com_periodo_anterior(): void
    {
        $user = User::factory()->create(['poker_bankroll' => 1800]);
        $table = $this->createTable();
        $tablePlayer = $this->createTablePlayer($table, $user);

        $currentHand = $this->createFinishedHand($table, 220, now()->subDays(3));
        $previousHand = $this->createFinishedHand($table, 130, now()->subDays(10));

        $this->createTransaction($user, $table, $tablePlayer, $currentHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(3));
        $this->createTransaction($user, $table, $tablePlayer, $currentHand, PokerBankrollTransaction::TYPE_PAYOUT, 220, now()->subDays(3));
        $this->createTransaction($user, $table, $tablePlayer, $previousHand, PokerBankrollTransaction::TYPE_BUY_IN, -100, now()->subDays(10));
        $this->createTransaction($user, $table, $tablePlayer, $previousHand, PokerBankrollTransaction::TYPE_PAYOUT, 130, now()->subDays(10));

        $response = $this->get(route('poker.players.show', ['user' => $user, 'advanced_period' => '7d']));

        $response->assertOk();

        $comparison = $response->viewData('page')['props']['profile']['advancedStatistics']['periodComparison'];

        $this->assertTrue($comparison['enabled']);
        $this->assertSame('Últimos 7 dias', $comparison['currentLabel']);
        $this->assertSame('Período anterior de 7 dias', $comparison['previousLabel']);
        $this->assertSame(120, $comparison['current']['netProfit']);
        $this->assertSame(30, $comparison['previous']['netProfit']);
        $this->assertSame(90, $comparison['delta']['netProfit']['value']);
        $this->assertSame('up', $comparison['delta']['netProfit']['direction']);
        $this->assertSame(90.0, $comparison['delta']['roi']['value']);
    }

    private function createTable(): PokerTable
    {
        return PokerTable::create([
            'name' => 'Mesa Estatísticas Avançadas',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);
    }

    private function createTablePlayer(PokerTable $table, User $user): PokerTablePlayer
    {
        return PokerTablePlayer::create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'is_bot' => false,
            'nickname' => $user->name,
            'stack' => 1000,
            'buy_in_amount' => 100,
            'buy_in_paid_at' => now(),
            'seat_number' => 1,
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    private function createPokerPlayer(PokerTable $table, User $user): PokerPlayer
    {
        return PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 1,
            'name' => $user->name,
            'type' => 'local_user',
            'stack' => 1000,
            'is_active' => true,
        ]);
    }

    private function createFinishedHand(PokerTable $table, int $pot, \DateTimeInterface $finishedAt): PokerHand
    {
        return PokerHand::create([
            'poker_table_id' => $table->id,
            'code' => (string) Str::uuid(),
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => $pot,
            'current_bet' => 0,
            'dealer_position' => 1,
            'winner' => 'player',
            'winner_label' => 'Jogador',
            'winning_hand_name' => 'Par',
            'started_at' => now()->subMinutes(10),
            'finished_at' => $finishedAt,
            'created_at' => $finishedAt,
            'updated_at' => $finishedAt,
        ]);
    }

    private function createTransaction(
        User $user,
        PokerTable $table,
        PokerTablePlayer $tablePlayer,
        PokerHand $hand,
        string $type,
        int $amount,
        \DateTimeInterface $createdAt,
    ): void {
        PokerBankrollTransaction::create([
            'user_id' => $user->id,
            'poker_table_id' => $table->id,
            'poker_hand_id' => $hand->id,
            'poker_table_player_id' => $tablePlayer->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => 1000,
            'balance_after' => 1000 + $amount,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createAction(
        PokerHand $hand,
        PokerTablePlayer $tablePlayer,
        PokerPlayer $pokerPlayer,
        string $action,
        int $amount,
        bool $isAllIn,
        ?\DateTimeInterface $actedAt = null,
    ): void {
        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $pokerPlayer->id,
            'street' => 'preflop',
            'action' => $action,
            'amount' => $amount,
            'pot_after_action' => $hand->pot,
            'metadata' => [
                'poker_table_player_id' => $tablePlayer->id,
                'is_all_in' => $isAllIn,
            ],
            'acted_at' => $actedAt ?? now(),
        ]);
    }
}
