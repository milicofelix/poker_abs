<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerRankingFinanceiroFaseDozeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_financeiro_exibe_leaderboard_por_bankroll_e_roi(): void
    {
        $leader = User::factory()->create(['name' => 'Jogador Líder', 'poker_bankroll' => 12500]);
        $runnerUp = User::factory()->create(['name' => 'Jogador Segundo', 'poker_bankroll' => 11000]);

        $leaderHand = $this->finishedHand();
        $runnerUpHand = $this->finishedHand();

        $this->transaction($leader, PokerBankrollTransaction::TYPE_BUY_IN, -1000, 10000, 9000);
        $this->transaction($leader, PokerBankrollTransaction::TYPE_PAYOUT, 3500, 9000, 12500, $leaderHand->id);
        $this->transaction($runnerUp, PokerBankrollTransaction::TYPE_BUY_IN, -1000, 10000, 9000);
        $this->transaction($runnerUp, PokerBankrollTransaction::TYPE_PAYOUT, 2000, 9000, 11000, $runnerUpHand->id);

        $this->actingAs($leader)
            ->get(route('poker.ranking.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Ranking')
                ->where('ranking.phase', '12.8')
                ->where('ranking.summary.players', 2)
                ->where('ranking.leaderboard.0.name', 'Jogador Líder')
                ->where('ranking.leaderboard.0.bankroll', 12500)
                ->where('ranking.leaderboard.0.netProfit', 2500)
                ->where('ranking.leaderboard.0.wins', 1)
                ->where('ranking.currentUser.name', 'Jogador Líder')
                ->where('ranking.currentUser.position', 1));
    }

    public function test_ranking_financeiro_lista_movimentacoes_recentes_do_ledger(): void
    {
        $user = User::factory()->create(['name' => 'Adriano', 'poker_bankroll' => 9000]);

        $this->transaction($user, PokerBankrollTransaction::TYPE_BUY_IN, -1000, 10000, 9000);

        $this->get(route('poker.ranking.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('ranking.recentTransactions.0.player', 'Adriano')
                ->where('ranking.recentTransactions.0.type', PokerBankrollTransaction::TYPE_BUY_IN)
                ->where('ranking.recentTransactions.0.amount', -1000)
                ->where('ranking.summary.transactions', 1));
    }

    private function finishedHand(): PokerHand
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa ranking financeiro',
            'status' => 'finished',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 3,
        ]);

        return PokerHand::query()->create([
            'poker_table_id' => $table->id,
            'code' => (string) str()->uuid(),
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => 100,
            'finished_at' => now(),
        ]);
    }

    private function transaction(User $user, string $type, int $amount, int $before, int $after, ?int $handId = null): PokerBankrollTransaction
    {
        return PokerBankrollTransaction::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'poker_hand_id' => $handId,
        ]);
    }
}
