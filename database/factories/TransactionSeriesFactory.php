<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\TransactionSeries;
use App\Models\Workspace;
use App\RecurrenceFrequency;
use App\SeriesKind;
use App\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionSeries>
 */
class TransactionSeriesFactory extends Factory
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
            'kind' => SeriesKind::Recurring,
            'transaction_type' => TransactionType::Expense,
            'amount_minor' => fake()->numberBetween(1000, 100000),
            'description' => fake()->words(3, true),
            'frequency' => RecurrenceFrequency::Monthly,
            'interval' => 1,
            'starts_on' => today(),
        ];
    }
}
