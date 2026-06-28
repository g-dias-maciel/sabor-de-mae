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

test('cliente pode repetir pedido anterior', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product1 = Product::factory()->withSinglePrice(20.00)->create();
    $product2 = Product::factory()->withSinglePrice(15.00)->create();
    $menu->products()->attach([$product1->id, $product2->id]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'total' => 55.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 2,
        'size' => 'M',
        'price_at_purchase' => 20.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'size' => 'M',
        'price_at_purchase' => 15.00,
    ]);

    Livewire::test(CustomerOrders::class)
        ->call('repeatOrder', $order->id)
        ->assertDispatched('restoreCart');

    Auth::logout();
});

test('repetir pedido exibe mensagem de confirmacao', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->withSinglePrice()->create();
    $menu->products()->attach($product->id);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'total' => 20.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'size' => 'M',
        'price_at_purchase' => 20.00,
    ]);

    Livewire::test(CustomerOrders::class)
        ->call('repeatOrder', $order->id)
        ->assertSee('Carrinho restaurado');

    Auth::logout();
});

test('repetir pedido so funciona para o proprio usuario', function () {
    $user1 = User::factory()->cliente()->create();
    Auth::login($user1);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->withSinglePrice()->create();
    $menu->products()->attach($product->id);

    $order = Order::factory()->create([
        'user_id' => $user1->id,
        'menu_id' => $menu->id,
        'total' => 20.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'size' => 'M',
        'price_at_purchase' => 20.00,
    ]);

    // Autentica como outro usuário
    Auth::logout();
    $user2 = User::factory()->cliente()->create();
    Auth::login($user2);

    // repeatOrder usa Auth::user()->orders()->findOrFail($orderId)
    // Se o pedido não pertence ao usuário, findOrFail lança ModelNotFoundException
    Livewire::test(CustomerOrders::class)
        ->call('repeatOrder', $order->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

