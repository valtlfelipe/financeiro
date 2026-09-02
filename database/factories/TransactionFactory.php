<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Workspace;
use App\TransactionType;
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
            'workspace_id' => Workspace::factory(),
            'account_id' => Account::factory(),
            'type' => TransactionType::Expense,
            'amount_minor' => fake()->numberBetween(1000, 100000),
            'description' => fake()->words(3, true),
            'due_on' => today(),
            'settled_at' => null,
        ];
    }
}
