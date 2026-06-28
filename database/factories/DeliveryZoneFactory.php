<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryZoneFactory extends Factory
{
    protected $model = DeliveryZone::class;

    public function definition(): array
    {
        $bairros = ['Centro', 'Jardim América', 'Vila Nova', 'Boa Vista', 'Copacabana', 'Pinheiros'];

        return [
            'neighborhood' => fake()->randomElement($bairros),
            'fee' => fake()->randomFloat(2, 0, 15),
        ];
    }

    public function gratis(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee' => 0,
        ]);
    }
}
