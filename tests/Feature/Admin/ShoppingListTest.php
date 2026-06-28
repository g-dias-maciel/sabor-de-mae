<?php

use App\Livewire\Admin\ShoppingList;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Livewire\Livewire;

test('shopping list mostra produtos agregados do menu ativo', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $produto = Product::factory()->create(['name' => 'Marmita de Frango']);

    // 2 pedidos confirmados, um com 2 unidades e outro com 3
    $order1 = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'confirmado',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order1->id,
        'product_id' => $produto->id,
        'quantity' => 2,
    ]);

    $order2 = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'confirmado',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order2->id,
        'product_id' => $produto->id,
        'quantity' => 3,
    ]);

    // Pedido não confirmado (não deve contar)
    $order3 = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'pendente',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order3->id,
        'product_id' => $produto->id,
        'quantity' => 10,
    ]);

    Livewire::test(ShoppingList::class)
        ->assertSee('Marmita de Frango')
        ->assertSee('5')
        ->assertSee('unidades');

    Carbon\Carbon::setTestNow();
});

test('shopping list mostra mensagem quando nao ha pedidos', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    Livewire::test(ShoppingList::class)
        ->assertSee('Nenhum pedido confirmado');

    Carbon\Carbon::setTestNow();
});
