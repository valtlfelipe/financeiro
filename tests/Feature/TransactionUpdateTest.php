<?php

use App\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionSeries;
use App\TransactionType;

function transactionUpdatePayload(Account $account, Category $category, array $overrides = []): array
{
    return [
        'account_id' => $account->id,
        'destination_account_id' => null,
        'category_id' => $category->id,
        'type' => TransactionType::Expense->value,
        'amount_minor' => 4590,
        'description' => 'Assinatura atualizada',
        'due_on' => '2026-09-15',
        'notes' => 'Novo plano',
        'settled' => false,
        ...$overrides,
    ];
}

test('updating a series occurrence requires an explicit scope', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->create(['type' => CategoryType::Expense]);
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create();
    $transaction = Transaction::factory()->for($workspace)->for($account)->for($series, 'series')->create([
        'category_id' => $category->id,
        'description' => 'Assinatura antiga',
    ]);

    $this->actingAs($user)->patch(route('transactions.update', $transaction), transactionUpdatePayload($account, $category))
        ->assertSessionHasErrors('scope');

    expect($transaction->refresh()->description)->toBe('Assinatura antiga');
});

test('updating only one occurrence preserves the rest of its series', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->create(['type' => CategoryType::Expense]);
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create();
    $transactions = Transaction::factory()->for($workspace)->for($account)->for($series, 'series')
        ->count(2)->sequence(
            ['category_id' => $category->id, 'description' => 'Atual', 'due_on' => '2026-09-10'],
            ['category_id' => $category->id, 'description' => 'Próximo', 'due_on' => '2026-10-10'],
        )->create();

    $this->actingAs($user)->patch(
        route('transactions.update', $transactions[0]),
        transactionUpdatePayload($account, $category, ['scope' => 'single']),
    )->assertSessionHasNoErrors()->assertRedirect();

    expect($transactions[0]->refresh()->description)->toBe('Assinatura atualizada')
        ->and($transactions[1]->refresh()->description)->toBe('Próximo')
        ->and($series->refresh()->description)->not->toBe('Assinatura atualizada');
});

test('updating this and future occurrences uses the original date boundary and preserves settled occurrences', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->create(['type' => CategoryType::Expense]);
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create();
    $transactions = Transaction::factory()->for($workspace)->for($account)->for($series, 'series')
        ->count(4)->sequence(
            ['category_id' => $category->id, 'description' => 'Anterior', 'due_on' => '2026-08-10'],
            ['category_id' => $category->id, 'description' => 'Atual', 'due_on' => '2026-09-10'],
            ['category_id' => $category->id, 'description' => 'Próximo', 'due_on' => '2026-09-12'],
            ['category_id' => $category->id, 'description' => 'Realizado', 'due_on' => '2026-10-10', 'settled_at' => '2026-09-01 12:00:00'],
        )->create();

    $this->actingAs($user)->patch(
        route('transactions.update', $transactions[1]),
        transactionUpdatePayload($account, $category, ['scope' => 'future', 'due_on' => '2026-09-20']),
    )->assertSessionHasNoErrors()->assertRedirect();

    expect($transactions[0]->refresh()->description)->toBe('Anterior')
        ->and($transactions[1]->refresh()->description)->toBe('Assinatura atualizada')
        ->and($transactions[2]->refresh()->description)->toBe('Assinatura atualizada')
        ->and($transactions[2]->amount_minor)->toBe(4590)
        ->and($transactions[3]->refresh()->description)->toBe('Realizado')
        ->and($series->refresh()->description)->toBe('Assinatura atualizada');
});
