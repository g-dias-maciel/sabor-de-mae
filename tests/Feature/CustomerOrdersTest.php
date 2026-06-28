<?php

use App\Livewire\CustomerOrders;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta
});

afterEach(function () {
    Carbon\Carbon::setTestNow();
});

test('cliente autenticado ve seus pedidos', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->withSinglePrice()->create(['name' => 'Marmita Fit']);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'total' => 100.00,
        'payment_status' => 'confirmado',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'size' => 'M',
    ]);

    Livewire::test(CustomerOrders::class)
        ->assertSee('Meus Pedidos')
        ->assertSee('Marmita Fit')
        ->assertSee('100,00');

    Auth::logout();
});

test('cliente nao autenticado e redirecionado', function () {
    Livewire::test(CustomerOrders::class)
        ->assertSee('Faça login');
});

test('cliente sem pedidos ve mensagem adequada', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    Livewire::test(CustomerOrders::class)
        ->assertSee('Nenhum pedido')
        ->assertSee('Você ainda não fez pedido esta semana');

    Auth::logout();
});

test('pedido atual mostra status do menu ativo', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menuAtivo = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'menu_id' => $menuAtivo->id,
        'total' => 50.00,
    ]);

    Livewire::test(CustomerOrders::class)
        ->assertSee('Pedido Semanal')
        ->assertSee('50,00');

    Auth::logout();
});

test('historico mostra pedidos passados', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    // Menu passado
    $menuPassado = Menu::factory()->create([
        'status' => 'encerrado',
        'start_date' => now()->subWeek()->startOfWeek(),
        'end_date' => now()->subWeek()->endOfWeek(),
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'menu_id' => $menuPassado->id,
        'total' => 75.00,
        'payment_status' => 'confirmado',
    ]);

    Livewire::test(CustomerOrders::class)
        ->assertSee('Histórico')
        ->assertSee('75,00');

    Auth::logout();
});
