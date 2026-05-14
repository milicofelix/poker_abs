<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AutenticacaoInertiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_consegue_visualizar_tela_de_login(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }

    public function test_usuario_consegue_visualizar_tela_de_cadastro(): void
    {
        $this->get(route('register'))
            ->assertOk();
    }

    public function test_usuario_consegue_criar_conta_e_fica_autenticado(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Adriano Freitas',
            'email' => 'adriano@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('poker.lobby'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Adriano Freitas',
            'email' => 'adriano@example.com',
        ]);
    }

    public function test_usuario_consegue_fazer_login_com_credenciais_validas(): void
    {
        $user = User::factory()->create([
            'email' => 'jogador@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'jogador@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('poker.lobby'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_nao_consegue_fazer_login_com_senha_invalida(): void
    {
        User::factory()->create([
            'email' => 'jogador@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'jogador@example.com',
                'password' => 'senha-errada',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_consegue_sair_do_sistema(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
