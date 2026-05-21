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

    public function test_trinca_vence_dois_pares_no_showdown(): void
    {
        $winner = (new ResolveLocalShowdownWinner())->execute([
            'bestHand' => [
                'name' => 'Dois pares',
                'rank' => 3,
                'kickers' => [9, 8, 10],
            ],
            'opponentBestHand' => [
                'name' => 'Trinca',
                'rank' => 4,
                'kickers' => [9, 10, 5],
            ],
        ]);

        $this->assertSame('opponent', $winner['player']);
        $this->assertSame('Oponente', $winner['label']);
        $this->assertSame('Trinca', $winner['handName']);
    }

    public function test_hierarquia_completa_de_maos_no_showdown(): void
    {
        $this->assertOpponentWins('Par', 2, 'Carta alta', 1);
        $this->assertOpponentWins('Dois pares', 3, 'Par', 2);
        $this->assertOpponentWins('Trinca', 4, 'Dois pares', 3);
        $this->assertOpponentWins('Sequência', 5, 'Trinca', 4);
        $this->assertOpponentWins('Flush', 6, 'Sequência', 5);
        $this->assertOpponentWins('Full house', 7, 'Flush', 6);
        $this->assertOpponentWins('Quadra', 8, 'Full house', 7);
        $this->assertOpponentWins('Straight flush', 9, 'Quadra', 8);
    }

    public function test_mesma_mao_resolve_por_kickers_em_ordem(): void
    {
        $winner = (new ResolveLocalShowdownWinner())->execute([
            'bestHand' => [
                'name' => 'Dois pares',
                'rank' => 3,
                'kickers' => [14, 10, 7],
            ],
            'opponentBestHand' => [
                'name' => 'Dois pares',
                'rank' => 3,
                'kickers' => [14, 10, 9],
            ],
        ]);

        $this->assertSame('opponent', $winner['player']);
        $this->assertSame('Oponente', $winner['label']);
        $this->assertSame('Dois pares', $winner['handName']);
    }

    private function assertOpponentWins(string $opponentName, int $opponentRank, string $playerName, int $playerRank): void
    {
        $winner = (new ResolveLocalShowdownWinner())->execute([
            'bestHand' => [
                'name' => $playerName,
                'rank' => $playerRank,
                'kickers' => [14, 13, 12, 11, 10],
            ],
            'opponentBestHand' => [
                'name' => $opponentName,
                'rank' => $opponentRank,
                'kickers' => [2],
            ],
        ]);

        $this->assertSame('opponent', $winner['player']);
        $this->assertSame('Oponente', $winner['label']);
        $this->assertSame($opponentName, $winner['handName']);
    }

}
