<?php

use App\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('list filters do not change the monthly summary', function (string $filter) {
    $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00'));
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
            ->where('subtotal.count', 1)
            ->where('subtotal.expense_minor', '80000')
            ->where("filters.{$filter}", (string) $value)
            ->where('summary.planned_income_minor', '200000')
            ->where('summary.planned_expense_minor', '80000')
            ->where('summary.opening_balance_minor', '0')
            ->where('summary.realized_balance_minor', '200000')
            ->where('summary.forecast_balance_minor', '120000'));
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
            ->where('summary.planned_income_minor', '0')
            ->where('summary.planned_expense_minor', '45000')
            ->where('summary.opening_balance_minor', '90000')
            ->where('summary.forecast_balance_minor', '45000'));
});

test('the default transaction month follows the workspace timezone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 02:30:00', 'UTC'));
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->get(route('transactions.index'))
        ->assertInertia(fn (Assert $page) => $page->where('month', '2026-09'));
});

test('account filter includes transfers in both directions without duplicates', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $otherAccount = Account::factory()->for($workspace)->create();
    $thirdAccount = Account::factory()->for($workspace)->create();
    $outgoing = Transaction::factory()->for($workspace)->for($account)->create([
        'destination_account_id' => $otherAccount->id,
        'type' => TransactionType::Transfer,
        'amount_minor' => 10000,
        'due_on' => '2026-09-10',
    ]);
    $incoming = Transaction::factory()->for($workspace)->for($otherAccount)->create([
        'destination_account_id' => $account->id,
        'type' => TransactionType::Transfer,
        'amount_minor' => 20000,
        'due_on' => '2026-09-11',
    ]);
    Transaction::factory()->for($workspace)->for($otherAccount)->create([
        'destination_account_id' => $thirdAccount->id,
        'type' => TransactionType::Transfer,
        'due_on' => '2026-09-12',
    ]);

    $this->actingAs($user)->get(route('transactions.index', [
        'month' => '2026-09',
        'account_id' => $account->id,
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('transactions', 2)
        ->where('transactions.0.id', $outgoing->id)
        ->where('transactions.1.id', $incoming->id)
        ->where('subtotal.count', 2)
        ->where('subtotal.transfer_minor', '30000'));
});

test('archived categories remain available only in historical filters', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $active = Category::factory()->for($workspace)->create(['name' => 'Ativa']);
    $archived = Category::factory()->for($workspace)->create([
        'name' => 'Arquivada',
        'is_archived' => true,
    ]);

    $this->actingAs($user)->get(route('transactions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $active->id)
            ->has('filterCategories', 2)
            ->where('filterCategories.0.id', $active->id)
            ->where('filterCategories.1.id', $archived->id)
            ->where('filterCategories.1.isArchived', true));
});

test('filtered subtotal keeps exact amounts separated by transaction type', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $otherAccount = Account::factory()->for($workspace)->create();
    $amount = 9007199254740992;

    foreach ([
        [TransactionType::Income, $amount],
        [TransactionType::Expense, 120000],
        [TransactionType::Transfer, 80000],
    ] as [$type, $transactionAmount]) {
        Transaction::factory()->for($workspace)->for($account)->create([
            'destination_account_id' => $type === TransactionType::Transfer ? $otherAccount->id : null,
            'type' => $type,
            'amount_minor' => $transactionAmount,
            'description' => 'Incluído no subtotal',
            'due_on' => '2026-09-10',
        ]);
    }
    Transaction::factory()->for($workspace)->for($account)->create([
        'type' => TransactionType::Expense,
        'amount_minor' => 99999,
        'description' => 'Fora do subtotal',
        'due_on' => '2026-09-10',
    ]);

    $this->actingAs($user)->get(route('transactions.index', [
        'month' => '2026-09',
        'search' => 'Incluído',
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('transactions', 3)
        ->where('subtotal.count', 3)
        ->where('subtotal.income_minor', (string) $amount)
        ->where('subtotal.expense_minor', '120000')
        ->where('subtotal.transfer_minor', '80000'));
});

test('overdue disclosure spans months while the list and subtotal remain monthly', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-10 02:30:00', 'UTC'));
    [$user, $workspace] = ownerWithWorkspace();
    $workspace->update(['timezone' => 'America/Sao_Paulo']);
    $account = Account::factory()->for($workspace)->create();
    $overdue = Transaction::factory()->for($workspace)->for($account)->create([
        'description' => 'Pendente de agosto',
        'amount_minor' => 50000,
        'due_on' => '2026-08-31',
    ]);
    $today = Transaction::factory()->for($workspace)->for($account)->create([
        'description' => 'Hoje',
        'amount_minor' => 70000,
        'due_on' => '2026-09-09',
    ]);
    Transaction::factory()->for($workspace)->for($account)->create([
        'description' => 'Já pago',
        'due_on' => '2026-08-30',
        'settled_at' => '2026-09-01 12:00:00',
    ]);
    Transaction::factory()->create(['due_on' => '2026-08-01']);

    $this->actingAs($user)->get(route('transactions.index', [
        'month' => '2026-09',
        'overdue' => 1,
        'search' => 'Hoje',
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('transactions', 1)
        ->where('transactions.0.id', $today->id)
        ->where('filters.search', 'Hoje')
        ->has('overdueTransactions', 1)
        ->where('overdueTransactions.0.id', $overdue->id)
        ->where('overdueTransactionsCount', 1)
        ->where('overdueOpen', true)
        ->where('subtotal.count', 1)
        ->where('subtotal.expense_minor', '70000')
        ->where('summary.planned_income_minor', '0'));
});

test('empty filtered results return zeroed exact subtotals', function () {
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->get(route('transactions.index', [
        'month' => '2026-09',
        'search' => 'Não existe',
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('transactions', 0)
        ->where('subtotal', [
            'count' => 0,
            'income_minor' => '0',
            'expense_minor' => '0',
            'transfer_minor' => '0',
        ]));
});
