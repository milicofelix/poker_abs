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
        $this->assertArrayHasKey('filters', $page['props']);
        $this->assertArrayHasKey('tables', $page['props']);
        $this->assertArrayHasKey('players', $page['props']);
    }

    public function test_historico_lista_maos_persistidas_com_ultima_acao(): void
    {
        [$table, $player, $hand] = $this->criarMaoComSnapshotDetalhado();

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
        $hands = $page['props']['hands']['data'];

        $this->assertCount(1, $hands);
        $this->assertSame($table->name, $hands[0]['table']);
        $this->assertSame('Finalizada', $hands[0]['statusLabel']);
        $this->assertSame('Showdown', $hands[0]['streetLabel']);
        $this->assertSame(1200, $hands[0]['pot']);
        $this->assertSame(2, $hands[0]['playersCount']);
        $this->assertSame(5, $hands[0]['boardCount']);
        $this->assertTrue($hands[0]['hasSidePot']);
        $this->assertSame(1, $hands[0]['actionsCount']);
        $this->assertSame('Você', $hands[0]['lastAction']['player']);
        $this->assertSame('Call', $hands[0]['lastAction']['action']);
    }

    public function test_detalhe_da_mao_exibe_cartas_board_side_pots_e_vencedor(): void
    {
        [, $player, $hand] = $this->criarMaoComSnapshotDetalhado();

        PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $player->id,
            'street' => 'river',
            'action' => 'All-in',
            'amount' => 500,
            'pot_after_action' => 1200,
            'metadata' => ['message' => 'Você entrou all-in.'],
            'acted_at' => now(),
        ]);

        $response = $this->get("/poker/hands/{$hand->id}");

        $response->assertOk();

        $page = $response->viewData('page');
        $detail = $page['props']['hand'];

        $this->assertSame('Poker/HandShow', $page['component']);
        $this->assertSame('Você', $detail['winner']['label']);
        $this->assertSame('Flush', $detail['winner']['handName']);
        $this->assertCount(5, $detail['board']);
        $this->assertCount(2, $detail['players']);
        $this->assertSame('A♠', $detail['players'][0]['cards'][0]['label']);
        $this->assertTrue($detail['sidePots']['hasSidePot']);
        $this->assertSame(1200, $detail['sidePots']['total']);
        $this->assertSame(1, $detail['stateHighlights']['dealerSeat']);
        $this->assertSame('All-in', $detail['actions'][0]['action']);
    }

    public function test_historico_filtra_por_mesa_e_periodo(): void
    {
        [$table] = $this->criarMaoComSnapshotDetalhado();

        PokerTable::create([
            'name' => 'Mesa sem resultado',
            'status' => 'waiting',
            'small_blind' => 5,
            'big_blind' => 10,
            'max_players' => 2,
        ]);

        $response = $this->get('/poker/hands?table_id='.$table->id.'&period=30d');

        $response->assertOk();

        $page = $response->viewData('page');
        $hands = $page['props']['hands']['data'];

        $this->assertCount(1, $hands);
        $this->assertSame($table->id, $hands[0]['tableId']);
        $this->assertSame('30d', $page['props']['filters']['period']);
    }

    /**
     * @return array{0: PokerTable, 1: PokerPlayer, 2: PokerHand}
     */
    private function criarMaoComSnapshotDetalhado(): array
    {
        $table = PokerTable::create([
            'name' => 'Mesa Fase 12.10',
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
            'stack' => 1800,
        ]);

        PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 2,
            'name' => 'Bot Forte',
            'type' => 'simple_bot',
            'stack' => 0,
        ]);

        $hand = PokerHand::create([
            'poker_table_id' => $table->id,
            'code' => (string) Str::uuid(),
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => 1200,
            'current_bet' => 0,
            'dealer_position' => 1,
            'winner' => 'local_user',
            'winner_label' => 'Você',
            'winning_hand_name' => 'Flush',
            'state_payload' => [
                'communityCards' => [
                    ['rank' => '2', 'suit' => '♠', 'label' => '2♠'],
                    ['rank' => '7', 'suit' => '♠', 'label' => '7♠'],
                    ['rank' => '9', 'suit' => '♠', 'label' => '9♠'],
                    ['rank' => 'K', 'suit' => '♣', 'label' => 'K♣'],
                    ['rank' => '4', 'suit' => '♦', 'label' => '4♦'],
                ],
                'multiSeat' => [
                    'dealerSeat' => 1,
                    'smallBlindSeat' => 2,
                    'bigBlindSeat' => 1,
                    'players' => [
                        [
                            'seat' => 1,
                            'name' => 'Você',
                            'type' => 'local_user',
                            'stack' => 1800,
                            'contribution' => 500,
                            'isAllIn' => false,
                            'cards' => [
                                ['rank' => 'A', 'suit' => '♠', 'label' => 'A♠'],
                                ['rank' => 'J', 'suit' => '♠', 'label' => 'J♠'],
                            ],
                        ],
                        [
                            'seat' => 2,
                            'name' => 'Bot Forte',
                            'type' => 'simple_bot',
                            'stack' => 0,
                            'contribution' => 700,
                            'isAllIn' => true,
                            'cards' => [
                                ['rank' => 'A', 'suit' => '♥', 'label' => 'A♥'],
                                ['rank' => 'A', 'suit' => '♦', 'label' => 'A♦'],
                            ],
                        ],
                    ],
                ],
                'bankrollSettlement' => [
                    'sidePots' => [
                        'hasSidePot' => true,
                        'total' => 1200,
                        'allInLevels' => [500, 700],
                        'pots' => [
                            [
                                'amount' => 1000,
                                'eligibleSeats' => [1, 2],
                                'winnerSeats' => [1],
                            ],
                            [
                                'amount' => 200,
                                'eligibleSeats' => [2],
                                'winnerSeats' => [2],
                            ],
                        ],
                    ],
                ],
            ],
            'started_at' => now()->subMinutes(8),
            'finished_at' => now(),
        ]);

        return [$table, $player, $hand];
    }
}
