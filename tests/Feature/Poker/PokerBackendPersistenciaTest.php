<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PokerBackendPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_uma_mesa_com_jogadores_e_uma_mao_local(): void
    {
        $table = PokerTable::create([
            'name' => 'Mesa local',
            'status' => 'playing',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $player = $table->players()->create([
            'seat' => 1,
            'name' => 'Você',
            'type' => 'local_user',
            'stack' => 1000,
        ]);

        $opponent = $table->players()->create([
            'seat' => 2,
            'name' => 'Oponente',
            'type' => 'simple_bot',
            'stack' => 1000,
        ]);

        $hand = $table->hands()->create([
            'code' => (string) Str::uuid(),
            'status' => 'running',
            'street' => 'pre_flop',
            'pot' => 30,
            'current_bet' => 20,
            'dealer_position' => 1,
            'started_at' => now(),
        ]);

        $this->assertDatabaseHas('poker_tables', [
            'id' => $table->id,
            'name' => 'Mesa local',
        ]);

        $this->assertCount(2, $table->players()->get());
        $this->assertSame('Você', $player->name);
        $this->assertSame('Oponente', $opponent->name);
        $this->assertSame('pre_flop', $hand->street);
    }

    public function test_registra_historico_de_acoes_da_mao(): void
    {
        $table = PokerTable::create([
            'name' => 'Mesa local',
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
            'pot' => 70,
            'current_bet' => 0,
            'started_at' => now(),
        ]);

        $action = PokerActionLog::create([
            'poker_hand_id' => $hand->id,
            'poker_player_id' => $player->id,
            'street' => 'flop',
            'action' => 'check',
            'amount' => 0,
            'pot_after_action' => 70,
            'metadata' => [
                'message' => 'Você pediu mesa.',
            ],
            'acted_at' => now(),
        ]);

        $this->assertSame('check', $action->action);
        $this->assertSame('Você pediu mesa.', $action->metadata['message']);
        $this->assertCount(1, $hand->actions()->get());
        $this->assertSame($player->id, $hand->actions()->first()->poker_player_id);
    }

    public function test_uma_mesa_nao_permite_dois_jogadores_no_mesmo_assento(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $table = PokerTable::create([
            'name' => 'Mesa local',
        ]);

        PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 1,
            'name' => 'Você',
            'type' => 'local_user',
            'stack' => 1000,
        ]);

        PokerPlayer::create([
            'poker_table_id' => $table->id,
            'seat' => 1,
            'name' => 'Oponente',
            'type' => 'simple_bot',
            'stack' => 1000,
        ]);
    }
}
