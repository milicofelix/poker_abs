<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerDetalheMaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_o_detalhe_de_uma_mao_persistida_com_jogadores_e_acoes(): void
    {
        $table = PokerTable::create([
            'name' => 'Mesa local',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $player = PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 1,
            'name' => 'Você',
            'type' => 'local_user',
            'stack' => 980,
            'is_active' => true,
        ]);

        $opponent = PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 2,
            'name' => 'Oponente',
            'type' => 'simple_bot',
            'stack' => 980,
            'is_active' => true,
        ]);

        $hand = PokerHand::create([
            'poker_table_id' => $table->id,
            'code' => 'mao-detalhe-teste',
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => 40,
            'current_bet' => 0,
            'dealer_position' => 1,
            'winner' => 'player',
            'winner_label' => 'Você',
            'winning_hand_name' => 'Par',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $player->id,
            'street' => 'pre_flop',
            'action' => 'Call',
            'amount' => 20,
            'pot_after_action' => 20,
            'metadata' => ['message' => 'Você pagou 20.'],
            'acted_at' => now()->subSeconds(20),
        ]);

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $opponent->id,
            'street' => 'pre_flop',
            'action' => 'Call',
            'amount' => 20,
            'pot_after_action' => 40,
            'metadata' => ['message' => 'Oponente pagou 20.'],
            'acted_at' => now()->subSeconds(10),
        ]);

        $this->get(route('poker.hands.show', $hand))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/HandShow')
                ->where('hand.code', 'mao-detalhe-teste')
                ->where('hand.table', 'Mesa local')
                ->where('hand.statusLabel', 'Finalizada')
                ->where('hand.streetLabel', 'Showdown')
                ->where('hand.pot', 40)
                ->where('hand.winner.label', 'Você')
                ->where('hand.winner.handName', 'Par')
                ->has('hand.players', 2)
                ->where('hand.players.0.name', 'Você')
                ->where('hand.players.1.name', 'Oponente')
                ->has('hand.actions', 2)
                ->where('hand.actions.0.action', 'Call')
                ->where('hand.actions.0.potAfterAction', 20)
                ->where('hand.actions.1.player', 'Oponente')
                ->where('hand.actions.1.potAfterAction', 40)
            );
    }
}
