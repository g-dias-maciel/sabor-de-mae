<?php

use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;

test('cliente pode fazer checkout na sexta feira', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(4)); // Sexta-feira

    $user = User::factory()->cliente()->create();
    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(25.00)->create();
    $menu->products()->attach($product->id);

    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    $service = app(CheckoutService::class);
    $order = $service->checkout(
        user: $user,
        menu: $menu,
        items: [
            ['product_id' => $product->id, 'quantity' => 2, 'size' => 'M'],
        ],
        deliveryZoneId: $zone->id,
        deliveryAddress: 'Rua Teste, 123',
        paymentMethod: 'pix',
    );

    expect($order)->not->toBeNull()
        ->and((float) $order->total)->toBe(55.00) // 2 * 25 + 5
        ->and($order->payment_method)->toBe('pix')
        ->and($order->payment_status)->toBe('pendente');

    Carbon\Carbon::setTestNow();
});

test('cliente pode fazer pre-venda no domingo para a proxima semana', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-06-28')); // Domingo

    $user = User::factory()->cliente()->create();
    // Menu da PRÓXIMA semana (pré-venda)
    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => '2026-06-29',
        'end_date' => '2026-07-05',
    ]);
    $product = Product::factory()->withSinglePrice(30.00)->create();
    $menu->products()->attach($product->id);

    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    $service = app(CheckoutService::class);
    $order = $service->checkout(
        user: $user,
        menu: $menu,
        items: [
            ['product_id' => $product->id, 'quantity' => 1, 'size' => 'M'],
        ],
        deliveryZoneId: $zone->id,
        deliveryAddress: 'Rua Teste, 123',
        paymentMethod: 'pix',
    );

    expect($order)->not->toBeNull()
        ->and((float) $order->total)->toBe(35.00) // 30 + 5
        ->and($order->payment_method)->toBe('pix');

    Carbon\Carbon::setTestNow();
});

test('cliente nao pode fazer checkout no domingo', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(6)); // Domingo

    $user = User::factory()->cliente()->create();
    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice()->create();
    $menu->products()->attach($product->id);

    $service = app(CheckoutService::class);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Pedidos encerrados para esta semana. O cardápio da próxima semana estará disponível em breve.');

    $service->checkout(
        user: $user,
        menu: $menu,
        items: [
            ['product_id' => $product->id, 'quantity' => 1, 'size' => 'M'],
        ],
    );

    Carbon\Carbon::setTestNow();
});

test('cliente nao pode fazer checkout com carrinho vazio', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta

    $user = User::factory()->cliente()->create();
    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $service = app(CheckoutService::class);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('O carrinho está vazio.');

    $service->checkout(user: $user, menu: $menu, items: []);

    Carbon\Carbon::setTestNow();
});

test('cliente nao pode fazer checkout em menu encerrado', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(2)); // Terça

    $user = User::factory()->cliente()->create();
    $menu = Menu::factory()->create([
        'status' => 'encerrado',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice()->create();
    $menu->products()->attach($product->id);

    $service = app(CheckoutService::class);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Pedidos não são aceitos neste momento.');

    $service->checkout(
        user: $user,
        menu: $menu,
        items: [
            ['product_id' => $product->id, 'quantity' => 1, 'size' => 'M'],
        ],
    );

    Carbon\Carbon::setTestNow();
});

test('checkout registra preco correto do produto no momento da compra', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta

    $user = User::factory()->cliente()->create();
    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(19.90)->create();
    $menu->products()->attach($product->id);

    $service = app(CheckoutService::class);
    $order = $service->checkout(
        user: $user,
        menu: $menu,
        items: [
            ['product_id' => $product->id, 'quantity' => 1, 'size' => 'M'],
        ],
    );

    $item = $order->items->first();
    expect((float) $item->price_at_purchase)->toBe(19.90);

    Carbon\Carbon::setTestNow();
});
