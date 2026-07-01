<?php

use App\Models\Product;
use App\Models\ProductPrice;

test('product price belongs to product', function () {
    $product = Product::factory()->create();
    $price = ProductPrice::factory()->create(['product_id' => $product->id]);

    expect($price->product)->toBeInstanceOf(Product::class)
        ->and($price->product->id)->toBe($product->id);
});

test('product price casts price as decimal', function () {
    $price = ProductPrice::factory()->create(['price' => 19.90]);

    // Cast decimal:2 retorna string (Laravel evita perda de precisão com floats)
    expect($price->price)->toBeString()
        ->and((float) $price->price)->toBe(19.90);
});

test('product price stores size P, M, or G', function () {
    $priceP = ProductPrice::factory()->sizeP()->create();
    $priceM = ProductPrice::factory()->sizeM()->create();
    $priceG = ProductPrice::factory()->sizeG()->create();

    expect($priceP->size)->toBe('P')
        ->and($priceM->size)->toBe('M')
        ->and($priceG->size)->toBe('G');
});

test('product price stock_limit is nullable integer', function () {
    $unlimited = ProductPrice::factory()->create(['stock_limit' => null]);
    $limited = ProductPrice::factory()->create(['stock_limit' => 20]);

    expect($unlimited->stock_limit)->toBeNull()
        ->and($limited->stock_limit)->toBe(20)
        ->and($limited->stock_limit)->toBeInt();
});

test('product price label retorna descricao legivel', function () {
    $priceP = ProductPrice::factory()->sizeP()->create();
    $priceM = ProductPrice::factory()->sizeM()->create();
    $priceG = ProductPrice::factory()->sizeG()->create();

    expect($priceP->label())->toContain('Pequena')
        ->and($priceG->label())->toContain('Grande');
});

test('product price shortLabel retorna label curto', function () {
    $priceP = ProductPrice::factory()->sizeP()->create();

    expect($priceP->shortLabel())->toContain('Pequena (500g)');
});
