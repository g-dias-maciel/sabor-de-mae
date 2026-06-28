<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'menu_id' => Menu::factory(),
            'total' => 0,
            'payment_method' => fake()->randomElement(['pix', 'dinheiro']),
            'payment_status' => 'pendente',
            'delivery_status' => 'pendente',
            'delivery_address' => fake()->address(),
            'delivery_zone_id' => DeliveryZone::factory(),
        ];
    }

    public function pix(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'pix',
        ]);
    }

    public function dinheiro(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'dinheiro',
        ]);
    }

    public function pago(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'confirmado',
        ]);
    }
}
