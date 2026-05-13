<?php

namespace Tests\Unit\Poker;

use App\Domain\Poker\Game\PokerAction;
use App\Domain\Poker\Game\RoundStreet;
use App\Services\Poker\ResolveLocalHandConclusion;
use PHPUnit\Framework\TestCase;

final class ResolveLocalHandConclusionTest extends TestCase
{
    public function test_quando_jogador_desiste_a_mao_finaliza_com_derrota(): void
    {
        $conclusion = (new ResolveLocalHandConclusion())->execute(
            playerAction: PokerAction::Fold,
            opponentAction: null,
            nextStreet: RoundStreet::Flop,
        );

        $this->assertTrue($conclusion->isFinished);
        $this->assertSame('player_fold', $conclusion->reason);
    }

    public function test_quando_oponente_desiste_a_mao_finaliza_com_vitoria(): void
    {
        $conclusion = (new ResolveLocalHandConclusion())->execute(
            playerAction: PokerAction::Raise,
            opponentAction: PokerAction::Fold,
            nextStreet: RoundStreet::River,
        );

        $this->assertTrue($conclusion->isFinished);
        $this->assertSame('opponent_fold', $conclusion->reason);
    }

    public function test_quando_chega_no_showdown_a_mao_finaliza_com_vencedor(): void
    {
        $conclusion = (new ResolveLocalHandConclusion())->execute(
            playerAction: PokerAction::Check,
            opponentAction: PokerAction::Check,
            nextStreet: RoundStreet::Showdown,
            winner: [
                'player' => 'player',
                'label' => 'Você',
                'handName' => 'Par',
            ],
        );

        $this->assertTrue($conclusion->isFinished);
        $this->assertSame('showdown', $conclusion->reason);
        $this->assertSame('player', $conclusion->winner['player']);
        $this->assertSame('Você', $conclusion->winner['label']);
    }
}
