<?php

use App\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionSeries;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('archiving a zeroed inactive account keeps history available without offering it for new entries', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-20 12:00:00'));
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0, 'balance_date' => '2026-09-01']);
    Account::factory()->for($workspace)->create();
    $income = Transaction::factory()->for($workspace)->for($account)->create([
        'type' => TransactionType::Income,
        'amount_minor' => 10000,
        'due_on' => '2026-09-10',
        'settled_at' => '2026-09-10 12:00:00',
    ]);
    Transaction::factory()->for($workspace)->for($account)->create([
        'type' => TransactionType::Expense,
        'amount_minor' => 10000,
        'due_on' => '2026-09-11',
        'settled_at' => '2026-09-11 12:00:00',
    ]);

    $this->actingAs($user)->from(route('accounts.index'))->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_archived' => true]);
    $this->assertNotSoftDeleted($income);
    $this->get(route('transactions.index', ['month' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('accounts', 1)
            ->has('filterAccounts', 2)
            ->where('filterAccounts.1.id', $account->id)
            ->where('filterAccounts.1.isArchived', true)
            ->where('transactions.0.account.id', $account->id));
});

test('an account with a current balance cannot be archived', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 100]);
    Account::factory()->for($workspace)->create();

    $this->actingAs($user)->delete(route('accounts.destroy', $account))
        ->assertSessionHasErrors('account');

    expect($account->fresh()->is_archived)->toBeFalse();
});

test('an account with pending or future movements cannot be archived', function (array $transaction): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-20 12:00:00'));
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0, 'balance_date' => '2026-09-01']);
    Account::factory()->for($workspace)->create();
    Transaction::factory()->for($workspace)->for($account)->create($transaction);

    $this->actingAs($user)->delete(route('accounts.destroy', $account))
        ->assertSessionHasErrors('account');

    expect($account->fresh()->is_archived)->toBeFalse();
})->with([
    'pending' => [['amount_minor' => 100, 'due_on' => '2026-09-10', 'settled_at' => null]],
    'future settled' => [['amount_minor' => 100, 'due_on' => '2026-10-10', 'settled_at' => '2026-09-10 12:00:00']],
]);

test('an account with an active recurrence cannot be archived', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0]);
    Account::factory()->for($workspace)->create();
    TransactionSeries::factory()->for($workspace)->for($account)->create([
        'starts_on' => $workspace->today()->addMonth(),
        'ends_on' => null,
    ]);

    $this->actingAs($user)->delete(route('accounts.destroy', $account))
        ->assertSessionHasErrors('account');

    expect($account->fresh()->is_archived)->toBeFalse();
});

test('the last active account cannot be archived', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0]);

    $this->actingAs($user)->delete(route('accounts.destroy', $account))
        ->assertSessionHasErrors('account');

    expect($account->fresh()->is_archived)->toBeFalse();
});

test('historical entries can keep their archived account when edited', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-20 12:00:00'));
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create(['initial_balance_minor' => 0, 'balance_date' => '2026-09-01']);
    Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->create(['type' => CategoryType::Both]);
    $income = Transaction::factory()->for($workspace)->for($account)->for($category)->create([
        'type' => TransactionType::Income,
        'amount_minor' => 10000,
        'due_on' => '2026-09-10',
        'settled_at' => '2026-09-10 12:00:00',
    ]);
    Transaction::factory()->for($workspace)->for($account)->for($category)->create([
        'type' => TransactionType::Expense,
        'amount_minor' => 10000,
        'due_on' => '2026-09-11',
        'settled_at' => '2026-09-11 12:00:00',
    ]);
    $this->actingAs($user)->delete(route('accounts.destroy', $account))->assertSessionHasNoErrors();

    $this->actingAs($user)->patch(route('transactions.update', $income), [
        'account_id' => $account->id,
        'destination_account_id' => null,
        'category_id' => $category->id,
        'type' => TransactionType::Income->value,
        'amount_minor' => 10000,
        'description' => 'Histórico corrigido',
        'due_on' => '2026-09-10',
        'settled' => true,
        'series_kind' => null,
    ])->assertSessionHasNoErrors();

    expect($income->fresh()->description)->toBe('Histórico corrigido');

    $this->actingAs($user)->patch(route('transactions.update', $income), [
        'account_id' => $account->id,
        'destination_account_id' => null,
        'category_id' => $category->id,
        'type' => TransactionType::Income->value,
        'amount_minor' => 10001,
        'description' => 'Não deve alterar saldo',
        'due_on' => '2026-09-10',
        'settled' => true,
        'series_kind' => null,
    ])->assertSessionHasErrors('account_id');

    expect($income->fresh()->amount_minor)->toBe(10000);
});

test('an account from another workspace cannot be archived', function () {
    [$user] = ownerWithWorkspace();
    $account = Account::factory()->create();

    $this->actingAs($user)->delete(route('accounts.destroy', $account))->assertNotFound();

    $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_archived' => false]);
});
