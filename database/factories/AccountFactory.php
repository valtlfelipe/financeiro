<?php

namespace Database\Factories;

use App\AccountType;
use App\Models\Account;
use App\Models\Workspace;
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
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(['Conta principal', 'Dinheiro', 'Poupança']),
            'type' => AccountType::Checking,
            'initial_balance_minor' => fake()->numberBetween(0, 500000),
            'balance_date' => today(),
            'icon' => 'wallet',
            'color' => '#148A62',
            'is_archived' => false,
        ];
    }
}
