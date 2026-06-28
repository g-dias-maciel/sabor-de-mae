<?php

use App\Livewire\Admin\DeliveryReport;
use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('delivery report lista todos os pedidos do menu ativo agrupados por dia', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $zone = DeliveryZone::factory()->create(['neighborhood' => 'Centro']);
    $user = User::factory()->create(['name' => 'Maria Silva', 'phone' => '(11) 99999-0000']);
    $product = Product::factory()->create(['name' => 'Marmita Fit']);

    // Associa o produto ao menu em um dia específico (Segunda-feira = 1)
    $menu->products()->attach($product->id, ['day_of_week' => 1]);

    $order = Order::factory()->create([
        'menu_id' => $menu->id,
        'user_id' => $user->id,
        'delivery_zone_id' => $zone->id,
        'delivery_address' => 'Rua das Flores, 123',
        'delivery_status' => 'pendente',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    Livewire::test(DeliveryReport::class)
        ->set('selectedMenuId', $menu->id)
        ->assertSee('Maria Silva')
        ->assertSee('(11) 99999-0000')
        ->assertSee('Rua das Flores, 123')
        ->assertSee('Marmita Fit')
        ->assertSee('⏳ Pendente');

    Carbon\Carbon::setTestNow();
});

test('delivery report mostra resumo por status', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $user = User::factory()->create();

    Order::factory()->create([
        'menu_id' => $menu->id,
        'user_id' => $user->id,
        'delivery_status' => 'pendente',
    ]);
    Order::factory()->create([
        'menu_id' => $menu->id,
        'user_id' => $user->id,
        'delivery_status' => 'entregue',
    ]);

    Livewire::test(DeliveryReport::class)
        ->set('selectedMenuId', $menu->id)
        ->assertSee('⏳ Pendentes')
        ->assertSee('✅ Entregues');

    Carbon\Carbon::setTestNow();
});
