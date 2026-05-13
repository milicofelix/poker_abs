<?php

namespace Tests\Unit\Poker;

use App\Application\Poker\PlayLocalPokerRoundAction;
use PHPUnit\Framework\TestCase;

final class PlayLocalPokerRoundActionTest extends TestCase
{
    public function test_ao_pagar_a_aposta_adiciona_valor_ao_pote_e_avanca_para_o_flop(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'pre_flop',
            'pot' => 30,
            'playerStack' => 1000,
            'opponentStack' => 1000,
            'currentBet' => 20,
        ], 'call');

        $this->assertSame('flop', $state['street']);
        $this->assertSame(70, $state['pot']);
        $this->assertSame(980, $state['playerStack']);
        $this->assertSame(980, $state['opponentStack']);
        $this->assertSame(0, $state['currentBet']);
        $this->assertSame(0, $state['playerStreetBet']);
        $this->assertSame(0, $state['opponentStreetBet']);
        $this->assertSame(0, $state['amountToCall']);
        $this->assertFalse($state['isFinished']);
    }


    public function test_raise_considera_apenas_a_diferenca_ja_comprometida_na_street(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'flop',
            'pot' => 50,
            'playerStack' => 990,
            'opponentStack' => 980,
            'currentBet' => 20,
            'playerStreetBet' => 10,
            'opponentStreetBet' => 20,
            'minimumRaise' => 10,
            'actionHistory' => [],
        ], 'raise', 60);

        $this->assertSame('turn', $state['street']);
        $this->assertSame(140, $state['pot']);
        $this->assertSame(940, $state['playerStack']);
        $this->assertSame(940, $state['opponentStack']);
        $this->assertSame(50, $state['actionHistory'][0]['amount']);
        $this->assertSame(40, $state['actionHistory'][1]['amount']);
        $this->assertSame(0, $state['currentBet']);
        $this->assertSame(0, $state['playerStreetBet']);
        $this->assertSame(0, $state['opponentStreetBet']);
    }

    public function test_check_nao_coloca_fichas_no_pote_quando_nao_existe_aposta_pendente(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'flop',
            'pot' => 70,
            'playerStack' => 980,
            'opponentStack' => 980,
            'currentBet' => 0,
            'playerStreetBet' => 0,
            'opponentStreetBet' => 0,
            'actionHistory' => [],
        ], 'check');

        $this->assertSame('turn', $state['street']);
        $this->assertSame(70, $state['pot']);
        $this->assertSame(980, $state['playerStack']);
        $this->assertSame(980, $state['opponentStack']);
        $this->assertSame(0, $state['actionHistory'][0]['amount']);
    }


    public function test_raise_abaixo_do_minimo_e_ajustado_para_o_menor_raise_valido(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'flop',
            'pot' => 40,
            'playerStack' => 990,
            'opponentStack' => 980,
            'currentBet' => 20,
            'playerStreetBet' => 10,
            'opponentStreetBet' => 20,
            'minimumRaise' => 20,
            'actionHistory' => [],
        ], 'raise', 25);

        $this->assertSame(30, $state['actionHistory'][0]['amount']);
        $this->assertSame(20, $state['actionHistory'][1]['amount']);
        $this->assertSame(90, $state['pot']);
        $this->assertSame(960, $state['playerStack']);
        $this->assertSame(960, $state['opponentStack']);
    }

    public function test_estado_retorna_limites_e_permissoes_de_aposta_para_o_frontend(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'flop',
            'pot' => 70,
            'playerStack' => 980,
            'opponentStack' => 980,
            'currentBet' => 0,
            'playerStreetBet' => 0,
            'opponentStreetBet' => 0,
            'minimumRaise' => 20,
            'smallBlind' => 10,
            'bigBlind' => 20,
            'dealerPosition' => 1,
            'actionHistory' => [],
        ], 'check');

        $this->assertTrue($state['canCheck']);
        $this->assertFalse($state['canCall']);
        $this->assertTrue($state['canRaise']);
        $this->assertSame(20, $state['minimumRaiseTo']);
        $this->assertSame(980, $state['maximumRaiseTo']);
        $this->assertSame(10, $state['smallBlind']);
        $this->assertSame(20, $state['bigBlind']);
        $this->assertSame(1, $state['dealerPosition']);
    }

    public function test_ao_desistir_finaliza_a_mao_com_conclusao_de_derrota(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'flop',
            'pot' => 50,
            'playerStack' => 980,
            'opponentStack' => 1000,
            'currentBet' => 20,
        ], 'fold');

        $this->assertSame('flop', $state['street']);
        $this->assertTrue($state['isFinished']);
        $this->assertSame('player_fold', $state['conclusion']['reason']);
        $this->assertCount(1, $state['actionHistory']);
    }

    public function test_quando_chega_ao_showdown_a_mao_e_finalizada(): void
    {
        $state = (new PlayLocalPokerRoundAction())->execute([
            'street' => 'river',
            'pot' => 100,
            'playerStack' => 900,
            'opponentStack' => 900,
            'currentBet' => 0,
            'actionHistory' => [],
            'bestHand' => [
                'name' => 'Flush',
                'rank' => 6,
                'kickers' => [14, 12, 10, 8, 3],
            ],
            'opponentBestHand' => [
                'name' => 'Sequência',
                'rank' => 5,
                'kickers' => [14],
            ],
        ], 'check');

        $this->assertSame('showdown', $state['street']);
        $this->assertTrue($state['isFinished']);
        $this->assertSame('showdown', $state['conclusion']['reason']);
        $this->assertSame('player', $state['conclusion']['winner']['player']);
        $this->assertSame('Você', $state['conclusion']['winner']['label']);
        $this->assertSame('Flush', $state['conclusion']['winner']['handName']);
    }
}
