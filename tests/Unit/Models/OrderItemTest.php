<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

test('order item belongs to order', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    expect($item->order)->toBeInstanceOf(Order::class)
        ->and($item->order->id)->toBe($order->id);
});

test('order item belongs to product', function () {
    $product = Product::factory()->withSinglePrice()->create();
    $item = OrderItem::factory()->create(['product_id' => $product->id]);

    expect($item->product)->toBeInstanceOf(Product::class)
        ->and($item->product->id)->toBe($product->id);
});

test('order item subtotal retorna preco multiplicado pela quantidade', function () {
    $item = OrderItem::factory()->create([
        'quantity' => 3,
        'price_at_purchase' => 25.00,
    ]);

    expect($item->subtotal())->toBe(75.00);
});

test('order item casts quantity as integer', function () {
    $item = OrderItem::factory()->create(['quantity' => 2]);

    expect($item->quantity)->toBeInt();
});

test('order item stores size', function () {
    $item = OrderItem::factory()->create(['size' => 'G']);

    expect($item->size)->toBe('G');
});
