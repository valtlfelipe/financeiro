<?php

use App\Actions\Transactions\AccountBalance;
use App\Models\Account;
use App\Models\Transaction;
use App\TransactionType;
use Carbon\CarbonImmutable;

test('an account balance date cannot be after today in its workspace', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 02:30:00', 'UTC'));
    [$user, $workspace] = ownerWithWorkspace();

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Conta futura',
        'type' => 'checking',
        'initial_balance_minor' => 100000,
        'balance_date' => '2026-10-01',
        'color' => '#148A62',
    ])->assertSessionHasErrors('balance_date');

    expect($workspace->accounts()->where('name', 'Conta futura')->exists())->toBeFalse();
});

test('legacy future-dated opening balances do not appear before their date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 12:00:00', 'America/Sao_Paulo'));
    [, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create([
        'initial_balance_minor' => 100000,
        'balance_date' => '2026-09-20',
    ]);

    $balance = app(AccountBalance::class);

    expect($balance->handle($account))->toBe('0')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-19')))->toBe('0')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-20')))->toBe('100000');
});

test('a zero opening balance includes earlier recorded movements', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'America/Sao_Paulo'));
    [, $workspace] = ownerWithWorkspace();
    $source = Account::factory()->for($workspace)->create([
        'initial_balance_minor' => 0,
        'balance_date' => '2026-09-04',
    ]);
    $destination = Account::factory()->for($workspace)->create([
        'initial_balance_minor' => 0,
        'balance_date' => '2026-09-04',
    ]);
    $datedSnapshot = Account::factory()->for($workspace)->create([
        'initial_balance_minor' => 10000,
        'balance_date' => '2026-09-04',
    ]);

    Transaction::factory()->for($workspace)->for($source)->create([
        'type' => TransactionType::Income,
        'amount_minor' => 1000,
        'due_on' => '2026-08-31',
        'settled_at' => '2026-08-31 12:00:00',
    ]);
    Transaction::factory()->for($workspace)->for($source)->create([
        'type' => TransactionType::Expense,
        'amount_minor' => 200,
        'due_on' => '2026-08-31',
        'settled_at' => '2026-08-31 12:00:00',
    ]);
    Transaction::factory()->for($workspace)->for($source)->create([
        'destination_account_id' => $destination->id,
        'type' => TransactionType::Transfer,
        'amount_minor' => 300,
        'due_on' => '2026-08-31',
        'settled_at' => '2026-08-31 12:00:00',
    ]);
    Transaction::factory()->for($workspace)->for($source)->create([
        'type' => TransactionType::Expense,
        'amount_minor' => 50,
        'due_on' => '2026-08-30',
        'settled_at' => null,
    ]);
    Transaction::factory()->for($workspace)->for($datedSnapshot)->create([
        'type' => TransactionType::Income,
        'amount_minor' => 900,
        'due_on' => '2026-08-31',
        'settled_at' => '2026-08-31 12:00:00',
    ]);

    $balance = app(AccountBalance::class);

    expect($balance->handle($source))->toBe('500')
        ->and($balance->settledThrough($source, CarbonImmutable::parse('2026-08-31')))->toBe('500')
        ->and($balance->projectedThrough($source, CarbonImmutable::parse('2026-09-30')))->toBe('450');
    expect($balance->handle($destination))->toBe('300')
        ->and($balance->handle($datedSnapshot))->toBe('10000');
});
