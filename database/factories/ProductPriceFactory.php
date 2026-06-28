<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['P', 'M', 'G']),
            'price' => fake()->randomFloat(2, 15, 40),
            'stock_limit' => null,
        ];
    }

    public function sizeP(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'P',
        ]);
    }

    public function sizeM(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'M',
        ]);
    }

    public function sizeG(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'G',
        ]);
    }

    public function withStockLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_limit' => $limit,
        ]);
    }
}
