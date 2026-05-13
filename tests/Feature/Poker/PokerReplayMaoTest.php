<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PokerReplayMaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exibe_replay_de_uma_mao_com_as_acoes_em_ordem(): void
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
            'stack' => 970,
            'is_active' => true,
        ]);

        $opponent = PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 2,
            'name' => 'Oponente',
            'type' => 'simple_bot',
            'stack' => 960,
            'is_active' => true,
        ]);

        $hand = PokerHand::create([
            'poker_table_id' => $table->id,
            'code' => 'mao-replay-teste',
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => 70,
            'current_bet' => 0,
            'dealer_position' => 1,
            'winner' => 'player',
            'winner_label' => 'Você',
            'winning_hand_name' => 'Dois pares',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $player->id,
            'street' => 'pre_flop',
            'action' => 'Call',
            'amount' => 10,
            'pot_after_action' => 40,
            'metadata' => ['message' => 'Você completou 10 fichas.'],
            'acted_at' => now()->subSeconds(30),
        ]);

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $opponent->id,
            'street' => 'flop',
            'action' => 'Raise',
            'amount' => 30,
            'pot_after_action' => 70,
            'metadata' => ['message' => 'Oponente aumentou para 30 fichas.'],
            'acted_at' => now()->subSeconds(20),
        ]);

        $this->get(route('poker.hands.replay', $hand))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Poker/HandReplay')
                ->where('replay.hand.code', 'mao-replay-teste')
                ->where('replay.hand.table', 'Mesa local')
                ->where('replay.summary.totalActions', 2)
                ->where('replay.summary.finalPot', 70)
                ->where('replay.summary.winnerLabel', 'Você')
                ->where('replay.summary.winningHandName', 'Dois pares')
                ->has('replay.steps', 2)
                ->where('replay.steps.0.number', 1)
                ->where('replay.steps.0.player', 'Você')
                ->where('replay.steps.0.action', 'Call')
                ->where('replay.steps.0.potAfterAction', 40)
                ->where('replay.steps.1.number', 2)
                ->where('replay.steps.1.player', 'Oponente')
                ->where('replay.steps.1.action', 'Raise')
                ->where('replay.steps.1.streetLabel', 'Flop')
                ->has('replay.streets', 2)
                ->where('replay.streets.0.label', 'Pré-flop')
                ->where('replay.streets.1.potAfterStreet', 70)
            );
    }
}
