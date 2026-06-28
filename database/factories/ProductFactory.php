<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $pratos = [
            'Marmita de Frango Grelhado',
            'Marmita de Carne de Panela',
            'Marmita de Strogonoff',
            'Marmita de Peixe Assado',
            'Marmita Vegetariana',
            'Marmita Fit',
            'Marmita de Feijoada',
            'Marmita de Lasanha',
        ];

        return [
            'name' => fake()->randomElement($pratos),
            'description' => fake()->sentence(),
            'type' => 'refeicao',
            'is_available' => true,
        ];
    }

    public function indisponivel(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Cria o produto com três tamanhos (P, M, G).
     */
    public function withPrices(float $priceP = 19.00, float $priceM = 22.00, float $priceG = 25.00): static
    {
        return $this->afterCreating(function (Product $product) use ($priceP, $priceM, $priceG) {
            $product->prices()->createMany([
                ['size' => 'P', 'price' => $priceP],
                ['size' => 'M', 'price' => $priceM],
                ['size' => 'G', 'price' => $priceG],
            ]);
        });
    }

    /**
     * Cria o produto com um único tamanho (M).
     */
    public function withSinglePrice(float $price = 10.00): static
    {
        return $this->afterCreating(function (Product $product) use ($price) {
            $product->prices()->create([
                'size' => 'M',
                'price' => $price,
            ]);
        });
    }
}
