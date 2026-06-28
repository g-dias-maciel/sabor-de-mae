<?php

use App\Models\Menu;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Fixa a data para controle do teste
    Carbon\Carbon::setTestNow('2024-06-05'); // Quarta-feira
});

afterEach(function () {
    Carbon\Carbon::setTestNow();
});

test('comando encerra menu ativo e cria proximo menu', function () {
    $menuAtivo = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => '2024-06-03',
        'end_date' => '2024-06-09',
    ]);
    $produto = Product::factory()->create();
    $menuAtivo->products()->attach($produto->id, ['day_of_week' => 3]);

    $exitCode = Artisan::call('menu:fechar-semanal');

    expect($exitCode)->toBe(0);

    // Menu anterior deve estar encerrado
    expect(Menu::find($menuAtivo->id)->status)->toBe('encerrado');

    // Deve existir um novo menu aberto começando na segunda seguinte
    $novoMenu = Menu::where('status', 'aberto')->first();
    expect($novoMenu)->not->toBeNull()
        ->and($novoMenu->start_date->format('Y-m-d'))->toBe('2024-06-10')
        ->and($novoMenu->end_date->format('Y-m-d'))->toBe('2024-06-16');

    // Produtos devem ter sido copiados
    expect($novoMenu->products)->toHaveCount(1)
        ->and($novoMenu->products->first()->id)->toBe($produto->id)
        ->and($novoMenu->products->first()->pivot->day_of_week)->toBe(3);
});

test('comando funciona mesmo sem menu ativo', function () {
    // Criar apenas menus encerrados
    Menu::factory()->create([
        'status' => 'encerrado',
        'start_date' => '2024-05-27',
        'end_date' => '2024-06-02',
    ]);

    $exitCode = Artisan::call('menu:fechar-semanal');

    expect($exitCode)->toBe(0);

    $novoMenu = Menu::where('status', 'aberto')->first();
    expect($novoMenu)->not->toBeNull();
});

test('comando retorna falha se nao houver menus no sistema', function () {
    Menu::query()->delete();

    $exitCode = Artisan::call('menu:fechar-semanal');

    expect($exitCode)->toBe(Command::FAILURE);
});
