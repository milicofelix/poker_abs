<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerRankingLocalTest extends TestCase
{
    use RefreshDatabase;

    public function test_jogador_consegue_abrir_o_ranking_local(): void
    {
        $response = $this->get('/poker/ranking');

        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertSame('Poker/Ranking', $page['component']);
        $this->assertArrayHasKey('ranking', $page['props']);
    }

    public function test_ranking_local_agrupa_maos_finalizadas_por_vencedor(): void
    {
        $table = PokerTable::create([
            'name' => 'Mesa local',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $this->createFinishedHand($table->id, 'player', 'Você', 'Par', 80);
        $this->createFinishedHand($table->id, 'player', 'Você', 'Dois pares', 120);
        $this->createFinishedHand($table->id, 'opponent', 'Oponente', 'Sequência', 60);
        $this->createFinishedHand($table->id, 'tie', 'Empate', 'Carta alta', 40);

        $response = $this->get('/poker/ranking');

        $response->assertOk();

        $ranking = $response->viewData('page')['props']['ranking'];

        $this->assertCount(3, $ranking);
        $this->assertSame('Você', $ranking[0]['label']);
        $this->assertSame(2, $ranking[0]['victories']);
        $this->assertSame(200, $ranking[0]['chipsWon']);
        $this->assertSame('Oponente', $ranking[1]['label']);
        $this->assertSame(1, $ranking[1]['victories']);
        $this->assertSame('Empate', $ranking[2]['label']);
        $this->assertSame(0, $ranking[2]['victories']);
        $this->assertSame(1, $ranking[2]['ties']);
    }

    private function createFinishedHand(
        int $tableId,
        string $winner,
        string $winnerLabel,
        string $winningHandName,
        int $pot,
    ): void {
        PokerHand::create([
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
}
