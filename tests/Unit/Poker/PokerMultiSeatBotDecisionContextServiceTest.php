<?php

namespace Tests\Unit\Poker;

use App\Services\Poker\PokerMultiSeatBotDecisionContextService;
use PHPUnit\Framework\TestCase;

final class PokerMultiSeatBotDecisionContextServiceTest extends TestCase
{
    public function test_contexto_multi_seat_informa_multiplos_oponentes_para_bot_da_vez(): void
    {
        $payload = (new PokerMultiSeatBotDecisionContextService())->forState([
            'pot' => 300,
            'currentBet' => 100,
            'multiSeat' => [
                'enabled' => true,
                'currentSeat' => 2,
                'players' => [
                    ['seatNumber' => 1, 'nickname' => 'Adriano', 'stack' => 900, 'streetBet' => 100],
                    ['seatNumber' => 2, 'nickname' => 'Bot TAG', 'isBot' => true, 'botProfile' => 'tag', 'botDifficulty' => 'hard', 'stack' => 860, 'streetBet' => 40],
                    ['seatNumber' => 3, 'nickname' => 'Bot LAG', 'isBot' => true, 'botProfile' => 'lag', 'stack' => 700, 'streetBet' => 100],
                ],
            ],
        ]);

        $this->assertCount(2, $payload);
        $this->assertSame('10.14', $payload[0]['phase']);
        $this->assertSame(2, $payload[0]['seatNumber']);
        $this->assertSame('tag', $payload[0]['profile']);
        $this->assertSame('hard', $payload[0]['difficulty']);
        $this->assertTrue($payload[0]['isCurrentTurn']);
        $this->assertSame(2, $payload[0]['activeOpponentCount']);
        $this->assertSame([1, 3], $payload[0]['activeOpponentSeats']);
        $this->assertSame(60, $payload[0]['amountToCall']);
        $this->assertSame(17, $payload[0]['potOdds']);
        $this->assertTrue($payload[0]['canCall']);
        $this->assertTrue($payload[0]['canRaise']);
    }

    public function test_contexto_ignora_bots_foldados_e_jogadores_reais(): void
    {
        $payload = (new PokerMultiSeatBotDecisionContextService())->forState([
            'pot' => 120,
            'currentBet' => 0,
            'multiSeat' => [
                'enabled' => true,
                'currentSeat' => 1,
                'players' => [
                    ['seatNumber' => 1, 'nickname' => 'Jogador', 'stack' => 1000],
                    ['seatNumber' => 2, 'nickname' => 'Bot foldado', 'isBot' => true, 'hasFolded' => true, 'stack' => 900],
                    ['seatNumber' => 3, 'nickname' => 'Bot ativo', 'role' => 'simple_bot', 'stack' => 850],
                ],
            ],
        ]);

        $this->assertCount(1, $payload);
        $this->assertSame(3, $payload[0]['seatNumber']);
        $this->assertSame(1, $payload[0]['activeOpponentCount']);
        $this->assertTrue($payload[0]['canCheck']);
        $this->assertFalse($payload[0]['canCall']);
    }

    public function test_contexto_fica_vazio_quando_multi_seat_nao_esta_ativo(): void
    {
        $payload = (new PokerMultiSeatBotDecisionContextService())->forState([
            'multiSeat' => [
                'enabled' => false,
                'players' => [
                    ['seatNumber' => 2, 'isBot' => true],
                ],
            ],
        ]);

        $this->assertSame([], $payload);
    }
}
