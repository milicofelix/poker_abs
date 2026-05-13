<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_pagina_inicial_redireciona_para_a_mesa_de_poker(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('poker.play'));
    }
}
