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
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
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
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
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
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
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
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
    ]);

    Livewire::test(ProductList::class)
        ->assertSee('Pedidos abertos até sábado 23:59');

    Carbon\Carbon::setTestNow();
});

test('cliente pode adicionar refeicao com salada ao carrinho', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
    ]);

    $refeicao = Product::factory()->withPrices(20.00, 22.00, 25.00)->create([
        'name' => 'Carne de Panela',
        'type' => 'refeicao',
    ]);
    $salada = Product::factory()->withSinglePrice(5.00)->create([
        'name' => 'Salada Verde',
        'type' => 'salada',
    ]);

    $menu->products()->attach($refeicao->id, ['day_of_week' => 1]);
    $menu->products()->attach($salada->id, ['day_of_week' => 1]);

    Livewire::test(ProductList::class)
        ->call('addToCart', $refeicao->id, 'P', $salada->id)
        ->assertSet('cart', function ($cart) use ($refeicao, $salada) {
            $key = $refeicao->id . ':P:s' . $salada->id;
            return isset($cart[$key])
                && str_contains($cart[$key]['name'], 'Salada Verde')
                && $cart[$key]['salad_product_id'] === $salada->id
                && $cart[$key]['salad_name'] === 'Salada Verde'
                && $cart[$key]['price'] === 25.00; // 20 (P) + 5 (salada)
        });

    Carbon\Carbon::setTestNow();
});

test('cliente pode adicionar extra ao carrinho', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
    ]);

    $extra = Product::factory()->withSinglePrice(8.00)->create([
        'name' => 'Suco Natural',
        'type' => 'extra',
    ]);
    $menu->products()->attach($extra->id);

    Livewire::test(ProductList::class)
        ->call('addToCart', $extra->id)
        ->assertSet('cart', function ($cart) use ($extra) {
            $key = $extra->id . ':M';
            return isset($cart[$key])
                && $cart[$key]['product_id'] === $extra->id
                && $cart[$key]['price'] === 8.00
                && $cart[$key]['quantity'] === 1;
        });

    Carbon\Carbon::setTestNow();
});

test('salada e extras aparecem por dia no cardapio', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
    ]);

    $refeicao = Product::factory()->withSinglePrice(20.00)->create([
        'name' => 'Marmita Teste',
        'type' => 'refeicao',
    ]);
    $salada = Product::factory()->withSinglePrice(5.00)->create([
        'name' => 'Salada do Dia',
        'type' => 'salada',
    ]);
    $extra = Product::factory()->withSinglePrice(4.00)->create([
        'name' => 'Refrigerante',
        'type' => 'extra',
    ]);

    $menu->products()->attach($refeicao->id, ['day_of_week' => 1]);
    $menu->products()->attach($salada->id);
    $menu->products()->attach($extra->id);

    Livewire::test(ProductList::class)
        ->assertSee('Marmita Teste')
        ->assertSee('Salada do Dia')
        ->assertSee('Adicionar salada')
        ->assertSee('Refrigerante')
        ->assertSee('Adicionar extra');

    Carbon\Carbon::setTestNow();
});
