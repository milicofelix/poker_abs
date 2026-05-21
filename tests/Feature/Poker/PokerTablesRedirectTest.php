<?php

namespace Tests\Feature\Poker;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokerTablesRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_rota_get_poker_tables_redireciona_para_lobby_sem_erro_405(): void
    {
        $this->get('/poker/tables')
            ->assertRedirect(route('poker.lobby'));
    }
}
