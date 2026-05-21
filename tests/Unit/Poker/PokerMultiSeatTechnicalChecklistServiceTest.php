<?php

namespace Tests\Unit\Poker;

use App\Models\Poker\PokerTable;
use App\Services\Poker\PokerMultiSeatTechnicalChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerMultiSeatTechnicalChecklistServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_tecnico_mantem_motor_heads_up_como_ativo(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa multi-seat futura',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 6,
        ]);

        $payload = (new PokerMultiSeatTechnicalChecklistService())->execute($table);

        $this->assertSame('8.5.8', $payload['phase']);
        $this->assertSame('heads_up', $payload['engineStatus']['activeEngine']);
        $this->assertSame('multi_seat', $payload['engineStatus']['futureEngine']);
        $this->assertFalse($payload['engineStatus']['multiSeatEnabled']);
        $this->assertTrue($payload['engineStatus']['isMultiSeatCandidate']);
        $this->assertSame(6, $payload['engineStatus']['declaredMaxPlayers']);
        $this->assertSame(2, $payload['engineStatus']['currentEngineMaxPlayers']);
    }

    public function test_checklist_lista_pontos_heads_up_antes_de_ativar_tres_mais(): void
    {
        $table = PokerTable::query()->create([
            'name' => 'Mesa heads-up',
            'status' => 'waiting',
            'small_blind' => 10,
            'big_blind' => 20,
            'max_players' => 2,
        ]);

        $payload = (new PokerMultiSeatTechnicalChecklistService())->execute($table);

        $areas = array_column($payload['headsUpDependencies'], 'area');

        $this->assertContains('PokerTableTurnActionService', $areas);
        $this->assertContains('PokerTurnTimeoutService e PokerBotTurnProcessor', $areas);
        $this->assertContains('MultiplayerPokerPrivateStateService', $areas);
        $this->assertGreaterThanOrEqual(6, count($payload['requiredBeforeEnablingThreePlus']));
        $this->assertFalse($payload['engineStatus']['isMultiSeatCandidate']);
    }
}
