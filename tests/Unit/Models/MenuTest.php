<?php

use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;

test('menu has many products', function () {
    $menu = Menu::factory()->create();
    $produto = Product::factory()->create();

    $menu->products()->attach($produto->id, ['day_of_week' => 1]);

    expect($menu->products)
        ->toHaveCount(1)
        ->and($menu->products->first()->pivot->day_of_week)
        ->toBe(1);
});

test('menu has many orders', function () {
    $menu = Menu::factory()->create();
    $order = Order::factory()->create(['menu_id' => $menu->id]);

    expect($menu->orders)
        ->toHaveCount(1)
        ->and($menu->orders->first())
        ->toBeInstanceOf(Order::class);
});

test('menu scope ativo retorna apenas menus abertos no periodo', function () {
    // Fixa uma quarta-feira para evitar comportamento especial de domingo
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(2)); // Terça-feira

    // Menu aberto no período atual
    $menuAtivo = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->subDays(3),
        'end_date' => now()->addDays(3),
    ]);

    // Menu encerrado
    Menu::factory()->create([
        'status' => 'encerrado',
        'start_date' => now()->subDays(3),
        'end_date' => now()->addDays(3),
    ]);

    // Menu aberto mas fora do período
    Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->addDays(10),
        'end_date' => now()->addDays(16),
    ]);

    $ativos = Menu::ativo()->get();

    expect($ativos)->toHaveCount(1)
        ->and($ativos->first()->id)->toBe($menuAtivo->id);

    Carbon\Carbon::setTestNow();
});

test('menu aceitaPedidos retorna true em dia de semana', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta-feira

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    expect($menu->aceitaPedidos())->toBeTrue();

    Carbon\Carbon::setTestNow();
});

test('menu aceitaPedidos retorna false no domingo', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(6)); // Domingo

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    expect($menu->aceitaPedidos())->toBeFalse();

    Carbon\Carbon::setTestNow();
});

test('menu scope ativo retorna menu da proxima semana no domingo', function () {
    // Domingo de uma semana específica
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-06-28')); // Domingo

    // Menu desta semana (encerrado no domingo)
    Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => '2026-06-22',
        'end_date' => '2026-06-28',
    ]);

    // Menu da próxima semana (pré-venda)
    $proximoMenu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => '2026-06-29',
        'end_date' => '2026-07-05',
    ]);

    $ativo = Menu::ativo()->first();

    expect($ativo)->not->toBeNull()
        ->and($ativo->id)->toBe($proximoMenu->id);

    Carbon\Carbon::setTestNow();
});

test('menu aceitaPedidos retorna true para pre-venda no domingo', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-06-28')); // Domingo

    // Menu da próxima semana (start_date no futuro)
    $proximoMenu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => '2026-06-29',
        'end_date' => '2026-07-05',
    ]);

    expect($proximoMenu->aceitaPedidos())->toBeTrue();

    Carbon\Carbon::setTestNow();
});

test('menu aceitaPedidos retorna false se status nao for aberto', function () {
    $menu = Menu::factory()->create([
        'status' => 'encerrado',
        'start_date' => now()->subDays(3),
        'end_date' => now()->addDays(3),
    ]);

    expect($menu->aceitaPedidos())->toBeFalse();
});

test('menu encerrar muda status para encerrado', function () {
    $menu = Menu::factory()->create(['status' => 'aberto']);

    $menu->encerrar();

    expect($menu->fresh()->status)->toBe('encerrado');
});

test('menu criarProximo cria novo menu apos o ultimo', function () {
    $ultimoMenu = Menu::factory()->create([
        'start_date' => '2024-01-01',
        'end_date' => '2024-01-07',
        'status' => 'encerrado',
    ]);

    $novoMenu = Menu::criarProximo();

    expect($novoMenu->start_date->format('Y-m-d'))->toBe('2024-01-08')
        ->and($novoMenu->end_date->format('Y-m-d'))->toBe('2024-01-14')
        ->and($novoMenu->status)->toBe('aberto');
});

test('menu casts dates properly', function () {
    $menu = Menu::factory()->create([
        'start_date' => '2024-06-10',
        'end_date' => '2024-06-16',
    ]);

    expect($menu->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($menu->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
