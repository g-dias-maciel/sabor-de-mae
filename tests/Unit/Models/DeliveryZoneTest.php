<?php

use App\Models\DeliveryZone;
use App\Models\Order;

test('delivery zone has many orders', function () {
    $zone = DeliveryZone::factory()->create();
    $order = Order::factory()->create(['delivery_zone_id' => $zone->id]);

    expect($zone->orders)
        ->toHaveCount(1)
        ->and($zone->orders->first())
        ->toBeInstanceOf(Order::class);
});

test('delivery zone casts fee correctly', function () {
    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    expect($zone->fee)->toBeNumeric()
        ->and((float) $zone->fee)->toBe(5.0);
});
