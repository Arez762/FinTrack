<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
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
            'name' => fake()->unique()->words(2, true).' Account',
            'type' => fake()->randomElement(['cash', 'bank', 'ewallet']),
            'initial_balance' => fake()->numberBetween(0, 10_000_000),
        ];
    }

    /**
     * Indicate that the account is a cash type.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cash',
        ]);
    }

    /**
     * Indicate that the account is a bank type.
     */
    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bank',
        ]);
    }

    /**
     * Indicate that the account is an e-wallet type.
     */
    public function ewallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ewallet',
        ]);
    }
}
