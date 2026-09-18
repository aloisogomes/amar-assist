<?php

namespace Database\Factories;

use App\Enums\FinanceType;
use App\Models\Finance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Finance>
 */
class FinanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(100, 500000),
            'date' => fake()->date(),
            'type' => fake()->randomElement(FinanceType::cases()),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FinanceType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FinanceType::Expense,
        ]);
    }
}
