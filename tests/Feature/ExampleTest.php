<?php

use App\Models\Menu;
use App\Models\Product;

test('the application returns a successful response', function () {
    // Configura dados mínimos para a página inicial funcionar
    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);

    $product = Product::factory()->withSinglePrice(20.00)->create();
    $menu->products()->attach($product->id);

    $response = $this->get('/');

    $response->assertStatus(200);
});
