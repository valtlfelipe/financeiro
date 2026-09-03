<?php

use App\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Inertia\Testing\AssertableInertia as Assert;

test('list filters do not change the monthly summary', function (string $filter) {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0, 'balance_date' => '2026-08-31']);
    $otherAccount = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0, 'balance_date' => '2026-08-31']);
    $category = Category::factory()->for($workspace)->create(['type' => CategoryType::Both]);
    $target = Transaction::factory()->for($workspace)->for($account)->for($category)->create([
        'description' => 'Aluguel', 'type' => 'expense', 'amount_minor' => 80000, 'due_on' => '2026-09-10',
    ]);
    Transaction::factory()->for($workspace)->for($otherAccount)->create([
        'description' => 'Salário', 'type' => 'income', 'amount_minor' => 200000, 'due_on' => '2026-09-05', 'settled_at' => '2026-09-05 12:00:00',
    ]);
    $value = match ($filter) {
        'search' => 'Aluguel',
        'type' => 'expense',
        'status' => 'pending',
        'account_id' => $account->id,
        'category_id' => $category->id,
    };

    $this->actingAs($user)->get(route('transactions.index', ['month' => '2026-09', $filter => $value]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Transactions/Index')
            ->has('transactions', 1)
            ->where('transactions.0.id', $target->id)
            ->where("filters.{$filter}", (string) $value)
            ->where('summary.planned_income_minor', 200000)
            ->where('summary.planned_expense_minor', 80000)
            ->where('summary.opening_balance_minor', 0)
            ->where('summary.realized_balance_minor', 200000)
            ->where('summary.forecast_balance_minor', 120000));
})->with(['search', 'type', 'status', 'account_id', 'category_id']);

test('changing months updates totals even when the filtered list is empty', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0, 'balance_date' => '2026-08-31']);
    Transaction::factory()->for($workspace)->for($account)->create(['description' => 'Setembro', 'type' => 'income', 'amount_minor' => 90000, 'due_on' => '2026-09-10']);
    Transaction::factory()->for($workspace)->for($account)->create(['description' => 'Outubro', 'type' => 'expense', 'amount_minor' => 45000, 'due_on' => '2026-10-10']);

    $this->actingAs($user)->get(route('transactions.index', ['month' => '2026-10', 'search' => 'Setembro']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('month', '2026-10')
            ->has('transactions', 0)
            ->where('summary.planned_income_minor', 0)
            ->where('summary.planned_expense_minor', 45000)
            ->where('summary.opening_balance_minor', 90000)
            ->where('summary.forecast_balance_minor', 45000));
});
