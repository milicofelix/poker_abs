<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerPhaseNineStateFeedbackService;
use Tests\TestCase;

final class PokerPhaseNineStateFeedbackServiceTest extends TestCase
{
    public function test_fase_nove_tres_documenta_estados_visuais_sem_alterar_regras(): void
    {
        $table = new PokerTable(['name' => 'Mesa teste', 'max_players' => 2]);

        $payload = (new PokerPhaseNineStateFeedbackService())->forTable($table, false);

        $this->assertSame('9.3', $payload['phase']);
        $this->assertSame('lobby', $payload['mode']);
        $this->assertFalse($payload['safeToChangeGameplay']);
        $this->assertCount(5, $payload['checklist']);
        $this->assertSame('turn-visibility', $payload['checklist'][0]['area']);
        $this->assertSame('waiting-state', $payload['checklist'][1]['area']);
        $this->assertSame('showdown-state', $payload['checklist'][2]['area']);
        $this->assertSame('winner-feedback', $payload['checklist'][3]['area']);
        $this->assertSame('locked', $payload['checklist'][4]['status']);
    }

    public function test_mesa_local_recebe_contexto_proprio_para_feedback_visual(): void
    {
        $table = new PokerTable(['name' => 'Mesa local', 'max_players' => 2]);

        $payload = (new PokerPhaseNineStateFeedbackService())->forTable($table, true);

        $this->assertSame('local', $payload['mode']);
        $this->assertStringContainsString('mesa local', mb_strtolower($payload['summary']));
        $this->assertFalse($payload['safeToChangeGameplay']);
    }
}
