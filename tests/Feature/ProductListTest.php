<?php

use App\Livewire\ProductList;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('product list mostra produtos do menu ativo', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $produto1 = Product::factory()->withSinglePrice()->create(['name' => 'Marmita de Frango']);
    $produto2 = Product::factory()->withSinglePrice()->create(['name' => 'Marmita de Carne']);
    $produtoInativo = Product::factory()->withSinglePrice()->indisponivel()->create(['name' => 'Indisponível']);

    $menu->products()->attach([$produto1->id, $produto2->id]);

    Livewire::test(ProductList::class)
        ->assertSee('Marmita de Frango')
        ->assertSee('Marmita de Carne')
        ->assertSee('Cardápio da Semana');

    Carbon\Carbon::setTestNow();
});

test('product list mostra mensagem quando nao ha menu ativo', function () {
    Livewire::test(ProductList::class)
        ->assertSee('Nenhum cardápio disponível');
});

test('cliente pode adicionar produto ao carrinho', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $produto = Product::factory()->withSinglePrice(20.00)->create();
    $menu->products()->attach($produto->id);

    Livewire::test(ProductList::class)
        ->call('addToCart', $produto->id, 'M')
        ->assertSet('cart', function ($cart) use ($produto) {
            $key = $produto->id . ':M';
            return isset($cart[$key])
                && $cart[$key]['quantity'] === 1
                && $cart[$key]['price'] === 20.00;
        });

    Carbon\Carbon::setTestNow();
});

test('cliente pode remover produto do carrinho', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $produto = Product::factory()->withSinglePrice()->create();
    $menu->products()->attach($produto->id);

    $component = Livewire::test(ProductList::class);
    $component->call('addToCart', $produto->id, 'M');
    $key = $produto->id . ':M';
    $component->call('removeFromCart', $key);

    expect($component->get('cart'))->toBeEmpty();

    Carbon\Carbon::setTestNow();
});

test('product list mostra status de pedidos abertos ou fechados', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta

    Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    Livewire::test(ProductList::class)
        ->assertSee('Pedidos abertos até sábado 23:59');

    Carbon\Carbon::setTestNow();
});
