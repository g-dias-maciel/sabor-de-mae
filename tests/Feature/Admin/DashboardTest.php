<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('dashboard exibe faturamento zero quando nao ha menu ativo', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('R$ 0,00')
        ->assertSee('0')
        ->assertSee('Não há menu ativo');
});

test('dashboard exibe faturamento do menu ativo', function () {
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
        'total' => 150.00,
        'payment_status' => 'confirmado',
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('R$ 150,00');

    Carbon\Carbon::setTestNow();
});

test('dashboard exibe total de marmitas vendidas', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $order = Order::factory()->create([
        'menu_id' => $menu->id,
        'total' => 100.00,
        'payment_status' => 'confirmado',
    ]);

    \App\Models\OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('3'); // marmitas

    Carbon\Carbon::setTestNow();
});

test('dashboard mostra status do menu aberto', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Aberto para Pedidos');

    Carbon\Carbon::setTestNow();
});
