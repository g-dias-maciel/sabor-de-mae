<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $product = Product::factory()->withSinglePrice()->create();
        $price = $product->prices->first() ?? ProductPrice::factory()->create(['product_id' => $product->id, 'size' => 'M']);

        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'quantity' => fake()->numberBetween(1, 5),
            'size' => $price->size,
            'price_at_purchase' => $price->price,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
