<?php

use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

test('order belongs to user', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    expect($order->user)->toBeInstanceOf(User::class)
        ->and($order->user->id)->toBe($user->id);
});

test('order belongs to menu', function () {
    $menu = Menu::factory()->create();
    $order = Order::factory()->create(['menu_id' => $menu->id]);

    expect($order->menu)->toBeInstanceOf(Menu::class)
        ->and($order->menu->id)->toBe($menu->id);
});

test('order has many items', function () {
    $order = Order::factory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    expect($order->items)
        ->toHaveCount(1)
        ->and($order->items->first())
        ->toBeInstanceOf(OrderItem::class);
});

test('order belongs to delivery zone', function () {
    $zone = DeliveryZone::factory()->create();
    $order = Order::factory()->create(['delivery_zone_id' => $zone->id]);

    expect($order->deliveryZone)->toBeInstanceOf(DeliveryZone::class);
});

test('order recalcularTotal soma itens e taxa de entrega', function () {
    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);
    $order = Order::factory()->create([
        'delivery_zone_id' => $zone->id,
        'total' => 0,
    ]);

    $product1 = Product::factory()->withSinglePrice(20.00)->create();
    $product2 = Product::factory()->withSinglePrice(15.00)->create();

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 2,
        'price_at_purchase' => 20.00,
        'size' => 'M',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'price_at_purchase' => 15.00,
        'size' => 'M',
    ]);

    $order->refresh();
    $order->recalcularTotal();

    // (2 * 20) + (1 * 15) + 5 = 60
    expect((float) $order->fresh()->total)->toBe(60.00);
});
