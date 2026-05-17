<?php

namespace Tests\Feature\Poker;

use App\Actions\Poker\JoinPokerTableAction;
use App\Actions\Poker\SitPokerTablePlayerAction;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\PokerTableSeatCapacityService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatBackendBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_multi_seat_identifica_primeiro_assento_livre_sem_ligar_engine_tres_mais(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat futura',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => User::factory()->create()->id,
            'nickname' => 'Jogador 1',
            'seat_number' => 1,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => User::factory()->create()->id,
            'nickname' => 'Jogador 2',
            'seat_number' => 2,
            'status' => 'online',
            'joined_at' => now(),
        ]);

        $service = app(PokerTableSeatCapacityService::class);

        $this->assertSame(3, $service->firstAvailableSeat($table));
        $this->assertTrue($service->hasRoomForAnotherPlayer($table));
        $this->assertSame('multi_seat_preparation', $service->payload($table)['engineMode']);
        $this->assertSame(2, $table->currentEngineMaxPlayers());
    }

    public function test_pode_sentarse_em_assento_futuro_declarado_mas_bloqueia_fora_da_capacidade(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa quatro lugares',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 4,
        ]);

        $player = PokerTablePlayer::query()->create([
            'poker_table_id' => $table->id,
            'user_id' => User::factory()->create()->id,
            'nickname' => 'Adriano',
            'status' => 'online',
            'joined_at' => now(),
        ]);

        $action = app(SitPokerTablePlayerAction::class);

        $seatedPlayer = $action->execute($table, $player, 4);

        $this->assertSame(4, $seatedPlayer->seat_number);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Assento inválido para esta mesa.');

        $action->execute($table, $seatedPlayer, 5);
    }

    public function test_entrada_na_mesa_respeita_capacidade_declarada_multi_seat(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa lotada',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 3,
        ]);

        $action = app(JoinPokerTableAction::class);

        foreach (range(1, 3) as $index) {
            $action->execute($table, User::factory()->create(['name' => 'Jogador '.$index]));
        }

        $this->assertSame(3, app(PokerTableSeatCapacityService::class)->activePlayersCount($table));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Mesa cheia. Escolha outra mesa no lobby.');

        $action->execute($table, User::factory()->create(['name' => 'Jogador excedente']));
    }
}
