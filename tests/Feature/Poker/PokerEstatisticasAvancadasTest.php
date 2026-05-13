<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerEstatisticasAvancadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_jogador_consegue_abrir_a_tela_de_estatisticas_avancadas(): void
    {
        $response = $this->get('/poker/statistics');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('Poker/Statistics', $page['component']);
        $this->assertArrayHasKey('statistics', $page['props']);
    }

    public function test_estatisticas_avancadas_sao_calculadas_com_base_no_historico_persistido(): void
    {
        [$table, $player, $opponent] = $this->createTableWithPlayers();

        $firstHand = $this->createFinishedHand($table->id, 'player', 'Você', 'Par', 80);
        $secondHand = $this->createFinishedHand($table->id, 'opponent', 'Oponente', 'Dois pares', 120);

        $this->createAction($firstHand->id, $player->id, 'preflop', 'Call', 10, 30);
        $this->createAction($firstHand->id, $opponent->id, 'preflop', 'Check', 0, 30);
        $this->createAction($firstHand->id, $player->id, 'flop', 'Raise', 40, 70);
        $this->createAction($firstHand->id, $opponent->id, 'flop', 'Fold', 0, 70);
        $this->createAction($secondHand->id, $player->id, 'preflop', 'Call', 10, 30);
        $this->createAction($secondHand->id, $opponent->id, 'preflop', 'Raise', 30, 60);

        $response = $this->get('/poker/statistics');

        $response->assertOk();

        $statistics = $response->viewData('page')['props']['statistics'];

        $this->assertSame(2, $statistics['overview']['handsPlayed']);
        $this->assertSame(6, $statistics['overview']['totalActions']);
        $this->assertSame(200, $statistics['overview']['totalPot']);
        $this->assertSame(100.0, $statistics['overview']['averagePot']);
        $this->assertSame(2, $statistics['overview']['showdowns']);

        $this->assertSame(1, $statistics['actions']['checks']);
        $this->assertSame(2, $statistics['actions']['calls']);
        $this->assertSame(2, $statistics['actions']['raises']);
        $this->assertSame(1, $statistics['actions']['folds']);

        $this->assertSame('Você', $statistics['players'][0]['label']);
        $this->assertSame(1, $statistics['players'][0]['victories']);
        $this->assertSame(50.0, $statistics['players'][0]['winRate']);
        $this->assertSame(60, $statistics['players'][0]['chipsInvested']);

        $this->assertSame('Oponente', $statistics['players'][1]['label']);
        $this->assertSame(1, $statistics['players'][1]['victories']);
        $this->assertSame(30, $statistics['players'][1]['chipsInvested']);

        $this->assertSame('Pré-flop', $statistics['streets'][0]['label']);
        $this->assertSame(4, $statistics['streets'][0]['actions']);
        $this->assertSame(50, $statistics['streets'][0]['chipsInvested']);
    }

    /**
     * @return array{0: PokerTable, 1: PokerPlayer, 2: PokerPlayer}
     */
    private function createTableWithPlayers(): array
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
            'stack' => 1000,
            'is_active' => true,
        ]);

        $opponent = PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 2,
            'name' => 'Oponente',
            'type' => 'simple_bot',
            'stack' => 1000,
            'is_active' => true,
        ]);

        return [$table, $player, $opponent];
    }

    private function createFinishedHand(
        int $tableId,
        string $winner,
        string $winnerLabel,
        string $winningHandName,
        int $pot,
    ): PokerHand {
        return PokerHand::create([
            'poker_table_id' => $tableId,
            'code' => (string) Str::uuid(),
            'status' => 'finished',
            'street' => 'showdown',
            'pot' => $pot,
            'current_bet' => 0,
            'dealer_position' => 1,
            'winner' => $winner,
            'winner_label' => $winnerLabel,
            'winning_hand_name' => $winningHandName,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }

    private function createAction(
        int $handId,
        int $playerId,
        string $street,
        string $action,
        int $amount,
        int $potAfterAction,
    ): void {
        PokerActionLog::create([
            'poker_hand_id' => $handId,
            'poker_player_id' => $playerId,
            'street' => $street,
            'action' => $action,
            'amount' => $amount,
            'pot_after_action' => $potAfterAction,
            'acted_at' => now(),
        ]);
    }
}
