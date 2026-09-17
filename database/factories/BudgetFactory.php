<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'amount_limit' => fake()->numberBetween(100_000, 10_000_000),
            'period' => 'month',
            'month' => (int) now()->format('n'),
            'year' => (int) now()->format('Y'),
        ];
    }

    /**
     * Indicate that the budget is for the current month.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'month',
            'month' => (int) now()->format('n'),
            'year' => (int) now()->format('Y'),
        ]);
    }

    /**
     * Indicate that the budget is for the whole current year.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => 'year',
            'month' => null,
            'year' => (int) now()->format('Y'),
        ]);
    }
}
