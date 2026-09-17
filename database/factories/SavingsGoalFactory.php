<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
class SavingsGoalFactory extends Factory
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
            'account_id' => null,
            'name' => fake()->unique()->words(2, true),
            'target_amount' => fake()->numberBetween(1_000_000, 50_000_000),
            'current_amount' => 0,
            'target_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'icon' => fake()->optional()->randomElement(['🎯', '🏠', '🚗', '✈️', '🏖️', '📱', '🎓', '💍', '🏥', '👶', '🐷', '💰', '🎁']),
            'color' => fake()->optional()->hexColor(),
            'is_completed' => false,
        ];
    }

    /**
     * Attach the goal to one of the user's accounts so that deposits create
     * expense transactions against it.
     */
    public function withAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    /**
     * Indicate that the goal is fully funded.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_amount' => $attributes['target_amount'],
            'is_completed' => true,
        ]);
    }
}
