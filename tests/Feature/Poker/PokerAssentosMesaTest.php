<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerAssentosMesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_jogador_consegue_sentar_em_um_assento_livre(): void
    {
        $user = User::factory()->create(['name' => 'Adriano']);

        $table = PokerTable::query()->create([
            'name' => 'Mesa com assentos',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $this->actingAs($user)
            ->postJson(route('poker.tables.join', $table));

        $this->actingAs($user)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('player.seatNumber', 3)
            ->assertJsonPath('seats.0.seatNumber', 3);

        $this->assertDatabaseHas('poker_table_players', [
            'user_id' => $user->id,
            'seat_number' => 3,
        ]);
    }

    public function test_nao_permite_dois_jogadores_no_mesmo_assento(): void
    {
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        $table = PokerTable::query()->create([
            'name' => 'Mesa bloqueada',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.join', $table));

        $this->actingAs($userTwo)
            ->postJson(route('poker.tables.join', $table));

        $this->actingAs($userOne)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 1,
            ])
            ->assertOk();

        $this->actingAs($userTwo)
            ->postJson(route('poker.tables.seat', $table), [
                'seat_number' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Este assento já está ocupado.');
    }
}
