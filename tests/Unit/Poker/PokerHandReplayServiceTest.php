<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerHandReplayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerHandReplayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monta_passos_de_replay_ordenados_pelo_horario_da_acao(): void
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
            'stack' => 990,
        ]);

        $hand = PokerHand::create([
            'poker_table_id' => $table->id,
            'code' => 'mao-replay-service',
            'status' => 'running',
            'street' => 'flop',
            'pot' => 40,
            'current_bet' => 0,
            'dealer_position' => 1,
            'started_at' => now(),
        ]);

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $player->id,
            'street' => 'flop',
            'action' => 'Check',
            'amount' => 0,
            'pot_after_action' => 40,
            'metadata' => ['message' => 'Você pediu mesa.'],
            'acted_at' => now()->subSeconds(10),
        ]);

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $player->id,
            'street' => 'pre_flop',
            'action' => 'Call',
            'amount' => 10,
            'pot_after_action' => 40,
            'metadata' => ['message' => 'Você pagou.'],
            'acted_at' => now()->subSeconds(20),
        ]);

        $replay = app(PokerHandReplayService::class)->build($hand);

        $this->assertSame('mao-replay-service', $replay['hand']['code']);
        $this->assertSame(2, $replay['summary']['totalActions']);
        $this->assertSame('Call', $replay['steps'][0]['action']);
        $this->assertSame('Pré-flop', $replay['steps'][0]['streetLabel']);
        $this->assertSame('Check', $replay['steps'][1]['action']);
        $this->assertSame('Flop', $replay['steps'][1]['streetLabel']);
    }
}
