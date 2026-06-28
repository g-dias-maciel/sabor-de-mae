<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = User::factory()->create([
            'name' => 'Atacante',
            'email' => 'attacker@evil.com',
            'password' => 'password',
            'is_admin' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Mãe',
            'email' => 'mae@sabordemae.com',
            'is_admin' => true,
        ]);
    }

    // =========================================================================
    // VULN #1: CRITICAL — Admin routes without admin role check [CORRIGIDO]
    // =========================================================================

    /** @test */
    public function cliente_nao_admin_recebe_403_no_dashboard(): void
    {
        $response = $this->actingAs($this->cliente)->get('/admin');

        $this->assertEquals(403, $response->status(),
            'Cliente comum deve receber 403 no dashboard admin.'
        );
    }

    /** @test */
    public function cliente_nao_admin_recebe_403_no_menu_manager(): void
    {
        $response = $this->actingAs($this->cliente)->get('/admin/cardapios');

        $this->assertEquals(403, $response->status(),
            'Cliente comum deve receber 403 no gerenciador de cardápios.'
        );
    }

    /** @test */
    public function cliente_nao_admin_recebe_403_no_product_manager(): void
    {
        $response = $this->actingAs($this->cliente)->get('/admin/produtos');

        $this->assertEquals(403, $response->status(),
            'Cliente comum deve receber 403 no gerenciador de produtos.'
        );
    }

    /** @test */
    public function cliente_nao_admin_recebe_403_na_shopping_list(): void
    {
        $response = $this->actingAs($this->cliente)->get('/admin/lista-compras');

        $this->assertEquals(403, $response->status(),
            'Cliente comum deve receber 403 na lista de compras.'
        );
    }

    /** @test */
    public function cliente_nao_admin_recebe_403_no_delivery_report(): void
    {
        $response = $this->actingAs($this->cliente)->get('/admin/entregas');

        $this->assertEquals(403, $response->status(),
            'Cliente comum deve receber 403 no relatório de entregas.'
        );
    }

    /** @test */
    public function admin_legitimo_acessa_dashboard_normalmente(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $this->assertEquals(200, $response->status(),
            'Admin deve conseguir acessar o dashboard.'
        );
    }

    /** @test */
    public function cliente_nao_logado_recebe_redirect_no_admin(): void
    {
        // Sem autenticação → redireciona para login (302)
        $response = $this->get('/admin');

        $this->assertEquals(302, $response->status(),
            'Usuário não logado deve ser redirecionado para login.'
        );
    }
}
