<?php

namespace Tests\Unit\Poker;

use App\Services\Poker\ResolveLocalShowdownWinner;
use PHPUnit\Framework\TestCase;

final class ResolveLocalShowdownWinnerTest extends TestCase
{
    public function test_quando_jogador_tem_mao_mais_forte_retorna_vencedor_voce(): void
    {
        $winner = (new ResolveLocalShowdownWinner())->execute([
            'bestHand' => [
                'name' => 'Dois pares',
                'rank' => 3,
                'kickers' => [14, 13, 8],
            ],
            'opponentBestHand' => [
                'name' => 'Par',
                'rank' => 2,
                'kickers' => [14, 12, 9, 7],
            ],
        ]);

        $this->assertSame('player', $winner['player']);
        $this->assertSame('Você', $winner['label']);
        $this->assertSame('Dois pares', $winner['handName']);
    }

    public function test_quando_oponente_tem_kicker_maior_retorna_vencedor_oponente(): void
    {
        $winner = (new ResolveLocalShowdownWinner())->execute([
            'bestHand' => [
                'name' => 'Par',
                'rank' => 2,
                'kickers' => [10, 14, 8, 7],
            ],
            'opponentBestHand' => [
                'name' => 'Par',
                'rank' => 2,
                'kickers' => [10, 14, 9, 7],
            ],
        ]);

        $this->assertSame('opponent', $winner['player']);
        $this->assertSame('Oponente', $winner['label']);
        $this->assertSame('Par', $winner['handName']);
    }

    public function test_quando_as_maos_sao_iguais_retorna_empate(): void
    {
        $winner = (new ResolveLocalShowdownWinner())->execute([
            'bestHand' => [
                'name' => 'Sequência',
                'rank' => 5,
                'kickers' => [14],
            ],
            'opponentBestHand' => [
                'name' => 'Sequência',
                'rank' => 5,
                'kickers' => [14],
            ],
        ]);

        $this->assertSame('tie', $winner['player']);
        $this->assertSame('Empate', $winner['label']);
        $this->assertSame('Sequência', $winner['handName']);
    }
}
