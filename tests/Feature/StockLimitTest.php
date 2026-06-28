<?php

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\CheckoutService;
use App\Models\User;

test('tamanho fica indisponivel quando stock_limit e atingido', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->create();
    $menu->products()->attach($product->id);

    ProductPrice::factory()->create([
        'product_id' => $product->id,
        'size' => 'P',
        'price' => 19.00,
        'stock_limit' => 2,
    ]);
    ProductPrice::factory()->create([
        'product_id' => $product->id,
        'size' => 'M',
        'price' => 22.00,
        'stock_limit' => null,
    ]);
    ProductPrice::factory()->create([
        'product_id' => $product->id,
        'size' => 'G',
        'price' => 25.00,
        'stock_limit' => 3,
    ]);

    // Cria 2 pedidos confirmados com tamanho P (atinge o limite)
    $order = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'confirmado',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size' => 'P',
        'quantity' => 2,
    ]);

    $available = $product->getAvailableSizes($menu);
    $sizes = array_map(fn($p) => $p->size, $available);

    expect($sizes)->not->toContain('P')  // Esgotado
        ->and($sizes)->toContain('M')     // Sem limite
        ->and($sizes)->toContain('G');    // Ainda disponível

    Carbon\Carbon::setTestNow();
});

test('tamanho disponivel quando stock_limit nao foi atingido', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->create();
    $menu->products()->attach($product->id);

    ProductPrice::factory()->create([
        'product_id' => $product->id,
        'size' => 'G',
        'price' => 25.00,
        'stock_limit' => 3,
    ]);

    // Apenas 1 pedido confirmado (ainda não atingiu limite de 3)
    $order = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'confirmado',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size' => 'G',
        'quantity' => 1,
    ]);

    $available = $product->getAvailableSizes($menu);
    $sizes = array_map(fn($p) => $p->size, $available);

    expect($sizes)->toContain('G');

    Carbon\Carbon::setTestNow();
});

test('produto sem stock_limit sempre mostra todos os tamanhos', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->withPrices()->create();
    $menu->products()->attach($product->id);

    // Cria vários pedidos, mas sem stock_limit — todos os tamanhos devem aparecer
    $order = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'confirmado',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size' => 'P',
        'quantity' => 100,
    ]);

    $available = $product->getAvailableSizes($menu);
    $sizes = array_map(fn($p) => $p->size, $available);

    expect($sizes)->toContain('P')
        ->and($sizes)->toContain('M')
        ->and($sizes)->toContain('G');

    Carbon\Carbon::setTestNow();
});

test('pedidos nao confirmados nao afetam stock', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->create();
    $menu->products()->attach($product->id);

    ProductPrice::factory()->create([
        'product_id' => $product->id,
        'size' => 'P',
        'price' => 19.00,
        'stock_limit' => 2,
    ]);

    // Pedido pendente (não conta para stock)
    $order = Order::factory()->create([
        'menu_id' => $menu->id,
        'payment_status' => 'pendente',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size' => 'P',
        'quantity' => 10,
    ]);

    $available = $product->getAvailableSizes($menu);
    $sizes = array_map(fn($p) => $p->size, $available);

    // P ainda deve estar disponível pois pedidos pendentes não contam
    expect($sizes)->toContain('P');

    Carbon\Carbon::setTestNow();
});

test('getAvailableSizes sem menu retorna todos os tamanhos', function () {
    $product = Product::factory()->withPrices()->create();

    $available = $product->getAvailableSizes(null);

    expect($available)->toHaveCount(3);
});
