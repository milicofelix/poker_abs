<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerBankrollTransaction;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerPerfilJogadorFaseDozeTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_publico_exibe_resumo_financeiro_do_jogador(): void
    {
        $user = User::factory()->create([
            'name' => 'Adriano Poker',
            'poker_bankroll' => 12500,
        ]);
        User::factory()->create(['poker_bankroll' => 15000]);
        User::factory()->create(['poker_bankroll' => 9000]);

        $table = PokerTable::query()->create([
            'name' => 'Mesa perfil',
            'status' => 'finished',
            'max_players' => 3,
        ]);
        $hand = PokerHand::query()->create([
            'poker_table_id' => $table->id,
            'code' => (string) Str::uuid(),
            'street' => 'showdown',
            'current_bet' => 0,
            'dealer_position' => 1,
            'status' => 'finished',
            'winner' => 'seat_1',
            'winner_label' => 'Adriano Poker',
            'winning_hand_name' => 'Flush',
            'pot' => 300,
            'finished_at' => now(),
        ]);
        $tablePlayer = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $user->id,
            'seat_number' => 1,
            'status' => 'offline',
            'stack' => 0,
            'buy_in_amount' => 1000,
        ]);

        PokerBankrollTransaction::query()->create([
            'user_id' => $user->id,
            'poker_table_id' => $table->id,
            'poker_hand_id' => $hand->id,
            'poker_table_player_id' => $tablePlayer->id,
            'type' => PokerBankrollTransaction::TYPE_BUY_IN,
            'amount' => -1000,
            'balance_before' => 10000,
            'balance_after' => 9000,
        ]);
        PokerBankrollTransaction::query()->create([
            'user_id' => $user->id,
            'poker_table_id' => $table->id,
            'poker_hand_id' => $hand->id,
            'poker_table_player_id' => $tablePlayer->id,
            'type' => PokerBankrollTransaction::TYPE_PAYOUT,
            'amount' => 300,
            'balance_before' => 9000,
            'balance_after' => 9300,
        ]);

        $this->get(route('poker.players.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Profile')
                ->where('profile.phase', '12.9')
                ->where('profile.player.name', 'Adriano Poker')
                ->where('profile.player.bankroll', 12500)
                ->where('profile.player.rankingPosition', 2)
                ->where('profile.stats.invested', 1000)
                ->where('profile.stats.payouts', 300)
                ->where('profile.stats.netProfit', -700)
                ->where('profile.stats.wins', 1)
                ->has('profile.recentTransactions', 2)
                ->has('profile.recentHands', 1)
                ->where('profile.recentHands.0.winningHand', 'Flush')
            );
    }

    public function test_rota_meu_perfil_exige_login_e_abre_o_perfil_do_usuario_logado(): void
    {
        $user = User::factory()->create([
            'name' => 'Jogador Logado',
            'poker_bankroll' => 7777,
        ]);

        $this->get(route('poker.profile.show'))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('poker.profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Profile')
                ->where('profile.player.name', 'Jogador Logado')
                ->where('profile.player.bankroll', 7777)
            );
    }

    public function test_ranking_financeiro_linka_para_perfil_do_jogador(): void
    {
        $user = User::factory()->create([
            'name' => 'Jogador Ranking',
            'poker_bankroll' => 13000,
        ]);

        PokerBankrollTransaction::query()->create([
            'user_id' => $user->id,
            'type' => PokerBankrollTransaction::TYPE_BUY_IN,
            'amount' => -1000,
            'balance_before' => 14000,
            'balance_after' => 13000,
        ]);

        $this->actingAs($user)
            ->get(route('poker.ranking.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/Ranking')
                ->where('ranking.currentUser.profileUrl', route('poker.players.show', $user))
                ->where('ranking.currentUser.name', 'Jogador Ranking')
                ->where('ranking.leaderboard.0.profileUrl', route('poker.players.show', $user))
            );
    }
}
