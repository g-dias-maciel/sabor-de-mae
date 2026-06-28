<?php

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;

test('product belongs to many menus', function () {
    $product = Product::factory()->create();
    $menu = Menu::factory()->create();

    $product->menus()->attach($menu->id);

    expect($product->menus)
        ->toHaveCount(1)
        ->and($product->menus->first())
        ->toBeInstanceOf(Menu::class);
});

test('product scope disponivel filtra indisponiveis', function () {
    $disponivel = Product::factory()->create(['is_available' => true]);
    Product::factory()->create(['is_available' => false]);

    $produtos = Product::disponivel()->get();

    expect($produtos)->toHaveCount(1)
        ->and($produtos->first()->id)->toBe($disponivel->id);
});

test('product has many prices', function () {
    $product = Product::factory()->withPrices()->create();

    expect($product->prices)->toHaveCount(3)
        ->and($product->prices->first())->toBeInstanceOf(ProductPrice::class);
});

test('product getPriceForSize retorna preco correto', function () {
    $product = Product::factory()->withPrices(19.00, 22.00, 25.00)->create();

    expect($product->getPriceForSize('P'))->toBe(19.00)
        ->and($product->getPriceForSize('M'))->toBe(22.00)
        ->and($product->getPriceForSize('G'))->toBe(25.00);
});

test('product getPriceForSize retorna M como default quando size null', function () {
    $product = Product::factory()->withSinglePrice(10.00)->create();

    expect($product->getPriceForSize(null))->toBe(10.00);
});

test('product hasSizes retorna true quando tem P e G', function () {
    $product = Product::factory()->withPrices()->create();

    expect($product->hasSizes())->toBeTrue();
});

test('product hasSizes retorna false quando so tem M', function () {
    $product = Product::factory()->withSinglePrice()->create();

    expect($product->hasSizes())->toBeFalse();
});

test('product getAvailableSizes filtra por stock_limit', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->create();
    ProductPrice::factory()->create(['product_id' => $product->id, 'size' => 'P', 'price' => 19.00, 'stock_limit' => 2]);
    ProductPrice::factory()->create(['product_id' => $product->id, 'size' => 'G', 'price' => 25.00, 'stock_limit' => null]); // unlimited

    // Cria 2 pedidos (stock P = 2, vendemos 2 = esgotado)
    $order = Order::factory()->create(['menu_id' => $menu->id, 'payment_status' => 'confirmado']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'size' => 'P',
        'quantity' => 2,
    ]);

    $available = $product->getAvailableSizes($menu);

    // P esgotado, G disponível
    $sizes = array_map(fn($p) => $p->size, $available);
    expect($sizes)->not->toContain('P')
        ->and($sizes)->toContain('G');

    Carbon\Carbon::setTestNow();
});

test('product getAvailableSizes retorna todos quando sem menu', function () {
    $product = Product::factory()->withPrices()->create();

    $available = $product->getAvailableSizes(null);

    expect($available)->toHaveCount(3);
});

test('product isPacoteSemanal, isExtra, isRefeicao', function () {
    $refeicao = Product::factory()->create(['type' => 'refeicao']);
    $pacote = Product::factory()->create(['type' => 'pacote_semanal']);
    $extra = Product::factory()->create(['type' => 'extra']);

    expect($refeicao->isRefeicao())->toBeTrue();
    expect($refeicao->isPacoteSemanal())->toBeFalse();

    expect($pacote->isPacoteSemanal())->toBeTrue();
    expect($pacote->isRefeicao())->toBeFalse();

    expect($extra->isExtra())->toBeTrue();
    expect($extra->isRefeicao())->toBeFalse();
});
