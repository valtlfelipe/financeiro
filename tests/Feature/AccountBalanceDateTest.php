<?php

use App\Actions\Transactions\AccountBalance;
use App\Models\Account;
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

    expect($balance->handle($account))->toBe(0)
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-19')))->toBe(0)
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-20')))->toBe(100000);
});
