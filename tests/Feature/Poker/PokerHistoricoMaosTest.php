<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerHistoricoMaosTest extends TestCase
{
    use RefreshDatabase;

    public function test_jogador_consegue_abrir_o_historico_de_maos(): void
    {
        $response = $this->get('/poker/hands');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('Poker/History', $page['component']);
        $this->assertArrayHasKey('hands', $page['props']);
    }

    public function test_historico_lista_maos_persistidas_com_ultima_acao(): void
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
        ]);

        $hand = PokerHand::create([
            'poker_table_id' => $table->id,
            'code' => (string) Str::uuid(),
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
            'street' => 'pre_flop',
            'action' => 'Call',
            'amount' => 20,
            'pot_after_action' => 20,
            'metadata' => [
                'message' => 'Você pagou 20 fichas.',
            ],
            'acted_at' => now(),
        ]);

        $response = $this->get('/poker/hands');

        $response->assertOk();

        $page = $response->viewData('page');
        $hands = $page['props']['hands'];

        $this->assertCount(1, $hands);
        $this->assertSame('Mesa local', $hands[0]['table']);
        $this->assertSame('Em andamento', $hands[0]['statusLabel']);
        $this->assertSame('Flop', $hands[0]['streetLabel']);
        $this->assertSame(40, $hands[0]['pot']);
        $this->assertSame(1, $hands[0]['actionsCount']);
        $this->assertSame('Você', $hands[0]['lastAction']['player']);
        $this->assertSame('Call', $hands[0]['lastAction']['action']);
    }
}
