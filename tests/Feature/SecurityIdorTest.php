<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityIdorTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // VULN #4: LOW — Enumeração de pedidos via repeatOrder
    // =========================================================================

    /** @test */
    public function vuln_low_repeat_order_nao_deve_usar_find_or_fail_sem_escopo_user(): void
    {
        $cliente = User::factory()->cliente()->create();
        $outroCliente = User::factory()->cliente()->create();

        $menu = Menu::factory()->aberto()->create();

        $product = Product::factory()->create(['name' => 'Test', 'type' => 'refeicao']);
        $product->prices()->create(['size' => 'P', 'price' => 19.00]);

        // Pedido do OUTRO cliente
        $order = Order::create([
            'user_id' => $outroCliente->id,
            'menu_id' => $menu->id,
            'total' => 19.00,
            'payment_method' => 'pix',
            'payment_status' => 'pendente',
            'delivery_status' => 'pendente',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'size' => 'P',
            'price_at_purchase' => 19.00,
        ]);

        $this->actingAs($cliente);

        // Verifica que o pedido NÃO pertence ao cliente logado
        $this->assertNotEquals($cliente->id, $order->user_id);

        // Tenta repetir um pedido de outro usuário
        $response = $this->get('/meus-pedidos');
        $response->assertOk();

        // O problema: CustomerOrders::repeatOrder() usa Order::findOrFail() sem escopo
        // Se o pedido existe, findOrFail retorna. Só depois verifica user_id.
        // Isso permite:
        // 1. Identificar se um ID de pedido existe (enumerar IDs)
        // 2. Se um pedido não pertence ao usuário → silêncio (sem erro 404)
        // 3. Comportamento diferente entre pedido existente vs inexistente
        //
        // CORREÇÃO: usar auth()->user()->orders()->findOrFail($orderId)
    }
}
