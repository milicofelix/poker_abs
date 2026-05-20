<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTablePlayer;
use App\Models\User;
use App\Services\Poker\LocalPokerPersistenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerFaseDozeSidePotTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_in_em_cascata_cria_main_pot_e_multiplos_side_pots_com_vencedores_independentes(): void
    {
        $users = User::factory()->count(4)->create(['poker_bankroll' => 10000]);
        $table = PokerTable::query()->create([
            'name' => 'Mesa Side Pot 12.6',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        foreach ($users as $index => $user) {
            $this->actingAs($user)->postJson(route('poker.tables.join', $table))->assertOk();
            $this->actingAs($user)->postJson(route('poker.tables.seat', $table), [
                'seat_number' => $index + 1,
            ])->assertOk();
        }

        $this->actingAs($users[0])
            ->postJson(route('poker.tables.new-hand', $table))
            ->assertOk()
            ->assertJsonPath('state.multiSeat.enabled', true);

        /** @var LocalPokerPersistenceService $persistence */
        $persistence = app(LocalPokerPersistenceService::class);
        $state = $persistence->currentStateForTable($table);
        $this->assertIsArray($state);

        $tablePlayers = PokerTablePlayer::query()
            ->where('poker_table_id', $table->id)
            ->orderBy('seat_number')
            ->get();

        $contributions = [1 => 100, 2 => 300, 3 => 600, 4 => 600];
        $stacksAfterAllIn = [1 => 900, 2 => 700, 3 => 400, 4 => 400];
        $hands = [
            1 => ['rank' => 9, 'kickers' => [14, 13, 12, 11, 10]],
            2 => ['rank' => 8, 'kickers' => [14, 12, 11, 10, 9]],
            3 => ['rank' => 7, 'kickers' => [13, 12, 11, 10, 9]],
            4 => ['rank' => 7, 'kickers' => [13, 12, 11, 10, 9]],
        ];

        foreach ($tablePlayers as $index => $tablePlayer) {
            $seatNumber = $index + 1;
            $tablePlayer->forceFill(['stack' => $stacksAfterAllIn[$seatNumber]])->save();
            $state['multiSeat']['players'][$index]['stack'] = $stacksAfterAllIn[$seatNumber];
            $state['multiSeat']['players'][$index]['handContribution'] = $contributions[$seatNumber];
            $state['multiSeat']['players'][$index]['totalCommitted'] = $contributions[$seatNumber];
            $state['multiSeat']['players'][$index]['bestHand'] = $hands[$seatNumber];
        }

        $state['isFinished'] = true;
        $state['street'] = 'showdown';
        $state['pot'] = array_sum($contributions);
        $state['multiSeat']['winnerSeats'] = [1];
        $state['multiSeat']['contributions'] = [
            'phase' => '12.6',
            'sidePotReady' => true,
            'totalPotTracked' => array_sum($contributions),
            'seats' => [
                '1' => ['seatNumber' => 1, 'tablePlayerId' => $tablePlayers[0]->id, 'total' => 100, 'byStreet' => ['pre_flop' => 100]],
                '2' => ['seatNumber' => 2, 'tablePlayerId' => $tablePlayers[1]->id, 'total' => 300, 'byStreet' => ['pre_flop' => 300]],
                '3' => ['seatNumber' => 3, 'tablePlayerId' => $tablePlayers[2]->id, 'total' => 600, 'byStreet' => ['pre_flop' => 600]],
                '4' => ['seatNumber' => 4, 'tablePlayerId' => $tablePlayers[3]->id, 'total' => 600, 'byStreet' => ['pre_flop' => 600]],
            ],
        ];
        $state['conclusion'] = [
            'isFinished' => true,
            'winner' => [
                'player' => 'seat:1',
                'seatNumber' => 1,
                'label' => 'Jogador 1',
                'handName' => 'Main pot vencedor',
            ],
            'message' => 'All-in em cascata resolvido com múltiplos side pots.',
        ];

        $settled = $persistence->persist($state);

        $this->assertTrue($settled['bankrollSettlement']['applied']);
        $this->assertSame('12.6', $settled['bankrollSettlement']['phase']);
        $this->assertSame([100, 300, 600], $settled['bankrollSettlement']['sidePots']['allInLevels']);
        $this->assertSame(1600, $settled['bankrollSettlement']['sidePots']['total']);

        $payouts = $settled['bankrollSettlement']['payoutsBySeat'];
        $this->assertSame(400, (int) ($payouts[1] ?? $payouts['1'] ?? 0));
        $this->assertSame(600, (int) ($payouts[2] ?? $payouts['2'] ?? 0));
        $this->assertSame(300, (int) ($payouts[3] ?? $payouts['3'] ?? 0));
        $this->assertSame(300, (int) ($payouts[4] ?? $payouts['4'] ?? 0));

        $pots = $settled['bankrollSettlement']['sidePots']['pots'];
        $this->assertSame('main', $pots[0]['type']);
        $this->assertSame(400, $pots[0]['amount']);
        $this->assertSame([1], $pots[0]['winnerSeats']);
        $this->assertSame(600, $pots[1]['amount']);
        $this->assertSame([2], $pots[1]['winnerSeats']);
        $this->assertSame(600, $pots[2]['amount']);
        $this->assertSame([3, 4], $pots[2]['winnerSeats']);

        $this->assertSame(1300, PokerTablePlayer::query()->whereKey($tablePlayers[0]->id)->value('stack'));
        $this->assertSame(1300, PokerTablePlayer::query()->whereKey($tablePlayers[1]->id)->value('stack'));
        $this->assertSame(700, PokerTablePlayer::query()->whereKey($tablePlayers[2]->id)->value('stack'));
        $this->assertSame(700, PokerTablePlayer::query()->whereKey($tablePlayers[3]->id)->value('stack'));
    }
}
