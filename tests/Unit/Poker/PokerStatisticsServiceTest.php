<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_estatisticas_zeradas_quando_nao_existem_maos(): void
    {
        $statistics = app(PokerStatisticsService::class)->localStatistics();

        $this->assertSame(0, $statistics['overview']['handsPlayed']);
        $this->assertSame(0, $statistics['overview']['totalActions']);
        $this->assertSame(0, $statistics['overview']['totalPot']);
        $this->assertSame(0, $statistics['overview']['averagePot']);
        $this->assertSame(0.0, $statistics['overview']['showdownRate']);
        $this->assertSame('Você', $statistics['players'][0]['label']);
        $this->assertSame('Oponente', $statistics['players'][1]['label']);
    }

    public function test_calcula_taxa_de_vitoria_por_jogador(): void
    {
        $table = PokerTable::create([
            'name' => 'Mesa local',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 1,
            'name' => 'Você',
            'type' => 'local_user',
            'stack' => 1000,
            'is_active' => true,
        ]);

        $this->createFinishedHand($table->id, 'player', 'Você', 80);
        $this->createFinishedHand($table->id, 'player', 'Você', 90);
        $this->createFinishedHand($table->id, 'opponent', 'Oponente', 100);
        $this->createFinishedHand($table->id, 'tie', 'Empate', 40);

        $statistics = app(PokerStatisticsService::class)->localStatistics();

        $this->assertSame(4, $statistics['overview']['handsPlayed']);
        $this->assertSame(310, $statistics['overview']['totalPot']);
        $this->assertSame(2, $statistics['players'][0]['victories']);
        $this->assertSame(50.0, $statistics['players'][0]['winRate']);
        $this->assertSame(1, $statistics['players'][1]['victories']);
        $this->assertSame(25.0, $statistics['players'][1]['winRate']);
    }

    private function createFinishedHand(int $tableId, string $winner, string $winnerLabel, int $pot): void
    {
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
            'winning_hand_name' => 'Par',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
    }
}
