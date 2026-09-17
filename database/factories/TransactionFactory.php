<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'account_id' => Account::factory(),
            'transfer_to_account_id' => null,
            'category_id' => null,
            'type' => fake()->randomElement(['income', 'expense', 'transfer']),
            'amount' => fake()->numberBetween(1_000, 10_000_000),
            'description' => fake()->sentence(),
            'transaction_date' => now(),
        ];
    }

    /**
     * Indicate that the transaction is an income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }

    /**
     * Indicate that the transaction is a transfer between two accounts.
     *
     * @param  array{from: object, to: object}  $accounts
     */
    public function transfer(object $from, object $to): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $from->id,
            'transfer_to_account_id' => $to->id,
            'type' => 'transfer',
        ]);
    }

    /**
     * Attach an expense category to the transaction.
     */
    public function withCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Assign the transaction to the given user and one of their accounts.
     */
    public function forUser(object $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'account_id' => $user->accounts()->inRandomOrder()->first()?->id
                ?? Account::factory()->for($user)->create()->id,
        ]);
    }
}
