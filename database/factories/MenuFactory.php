<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-4 weeks', '+4 weeks');

        return [
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify('+6 days'),
            'status' => 'aberto',
        ];
    }

    public function aberto(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aberto',
        ]);
    }

    public function encerrado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'encerrado',
        ]);
    }

    public function planejamento(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planejamento',
        ]);
    }

    /**
     * Menu da semana atual (segunda a domingo atual).
     */
    public function semanaAtual(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->startOfWeek(),
            'end_date' => now()->endOfWeek(),
        ]);
    }
}
