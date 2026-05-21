<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerMultiSeatDealPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatDealPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepara_duas_cartas_privadas_para_cada_assento_ativo(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa 3+ preview',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        foreach ([1, 2, 3] as $seat) {
            $user = User::factory()->create();

            PokerTablePlayer::query()->create([
                'poker_table_id' => $table->id,
                'user_id' => $user->id,
                'nickname' => "Jogador {$seat}",
                'stack' => 1000,
                'seat_number' => $seat,
                'status' => 'active',
                'joined_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        $payload = app(PokerMultiSeatDealPreviewService::class)->preview($table->fresh());

        $this->assertSame('10.3', $payload['phase']);
        $this->assertFalse($payload['enabledInMainEngine']);
        $this->assertSame(4, $payload['declaredMaxPlayers']);
        $this->assertSame(3, $payload['activeSeatedPlayers']);
        $this->assertCount(3, $payload['seatHands']);
        $this->assertCount(5, $payload['communityCards']);
        $this->assertSame(41, $payload['remainingDeckCards']);

        foreach ($payload['seatHands'] as $seatHand) {
            $this->assertSame(2, $seatHand['cardsCount']);
            $this->assertCount(2, $seatHand['cards']);
            $this->assertTrue($seatHand['isPrivatePayloadReady']);
        }
    }

    public function test_preview_nao_inclui_jogador_sem_assento_ativo(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa com observador',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $seatedUser = User::factory()->create();
        $waitingUser = User::factory()->create();

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $seatedUser->id,
            'nickname' => 'Sentado',
            'stack' => 1000,
            'seat_number' => 2,
            'status' => 'active',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => $waitingUser->id,
            'nickname' => 'Sem assento',
            'stack' => 1000,
            'seat_number' => null,
            'status' => 'waiting',
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        $payload = app(PokerMultiSeatDealPreviewService::class)->preview($table->fresh());

        $this->assertSame(1, $payload['activeSeatedPlayers']);
        $this->assertCount(1, $payload['seatHands']);
        $this->assertSame('Sentado', $payload['seatHands'][0]['nickname']);
    }
}
