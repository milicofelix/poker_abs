<?php

namespace Tests\Feature\Poker;

use App\Models\Poker\PokerActionLog;
use App\Models\Poker\PokerHand;
use App\Models\Poker\PokerPlayer;
use App\Models\Poker\PokerTable;
use App\Models\Poker\PokerTableSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerPersistenciaFluxoLocalTest extends TestCase
{
    use RefreshDatabase;

    public function test_abrir_a_tela_do_poker_cria_mesa_jogadores_e_mao_no_banco(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $this->assertDatabaseHas('poker_tables', [
            'name' => 'Mesa local',
            'status' => 'playing',
        ]);

        $this->assertDatabaseHas('poker_players', [
            'name' => 'Você',
            'type' => 'local_user',
            'seat' => 1,
        ]);

        $this->assertDatabaseHas('poker_players', [
            'name' => 'Oponente',
            'type' => 'simple_bot',
            'seat' => 2,
        ]);

        $this->assertSame(1, PokerTable::query()->count());
        $this->assertSame(2, PokerPlayer::query()->count());
        $this->assertSame(1, PokerHand::query()->count());
        $this->assertSame(2, PokerTableSeat::query()->count());

        $this->assertArrayHasKey('persistence', session('poker.local_hand'));
        $this->assertArrayHasKey('tableSeats', session('poker.local_hand'));
    }


    public function test_mesa_local_inicia_com_acoes_liberadas_para_o_jogador(): void
    {
        $response = $this->get('/poker');

        $response->assertOk();

        $hand = session('poker.local_hand');

        $this->assertIsArray($hand);
        $this->assertFalse((bool) ($hand['isFinished'] ?? true));
        $this->assertTrue((bool) ($hand['canAct'] ?? false));
        $this->assertTrue((bool) ($hand['canCall'] ?? false));
        $this->assertFalse((bool) ($hand['canCheck'] ?? true));
    }

    public function test_mesa_local_mantem_acoes_liberadas_apos_jogada_nao_terminal(): void
    {
        $this->get('/poker')->assertOk();

        $response = $this->postJson('/poker/actions', [
            'action' => 'call',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.isFinished', false)
            ->assertJsonPath('state.canAct', true);
    }

    public function test_acao_do_jogador_e_resposta_do_oponente_sao_gravadas_no_historico_do_banco(): void
    {
        $this->get('/poker')->assertOk();

        $response = $this->postJson('/poker/actions', [
            'action' => 'call',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.street', 'flop');

        $this->assertSame(2, PokerActionLog::query()->count());

        $this->assertDatabaseHas('poker_action_logs', [
            'action' => 'Call',
            'amount' => 10,
            'pot_after_action' => 40,
        ]);

        $this->assertDatabaseHas('poker_action_logs', [
            'action' => 'Call',
            'amount' => 0,
            'pot_after_action' => 40,
        ]);

        $this->assertDatabaseHas('poker_hands', [
            'street' => 'flop',
            'pot' => 40,
            'current_bet' => 0,
        ]);
    }

    public function test_mao_finalizada_por_fold_atualiza_status_no_banco(): void
    {
        $this->get('/poker')->assertOk();

        $response = $this->postJson('/poker/actions', [
            'action' => 'fold',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('state.isFinished', true);

        $hand = PokerHand::query()->firstOrFail();

        $this->assertSame('finished', $hand->status);
        $this->assertNotNull($hand->finished_at);
    }
}
