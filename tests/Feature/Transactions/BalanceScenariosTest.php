<?php

use App\Actions\Transactions\AccountBalance;
use App\Actions\Transactions\CreateTransactions;
use App\Actions\Transactions\MonthlySummary;
use App\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\RecurrenceFrequency;
use App\SeriesKind;
use App\Support\MinorAmount;
use App\TransactionType;
use Carbon\CarbonImmutable;

beforeEach(function () {
    [$this->user, $this->workspace] = ownerWithWorkspace();
    $this->account = Account::factory()->create([
        'workspace_id' => $this->workspace->id,
        'initial_balance_minor' => 0,
        'balance_date' => '2026-01-01',
    ]);
    $this->destination = Account::factory()->create([
        'workspace_id' => $this->workspace->id,
        'initial_balance_minor' => 0,
        'balance_date' => '2026-01-01',
    ]);
    $this->expenseCategory = Category::factory()->create(['workspace_id' => $this->workspace->id, 'type' => CategoryType::Expense]);
    $this->incomeCategory = Category::factory()->create(['workspace_id' => $this->workspace->id, 'type' => CategoryType::Income]);
});

/** @param array<string, mixed> $attributes */
function movement(array $attributes): Transaction
{
    return Transaction::factory()->create([
        'workspace_id' => test()->workspace->id,
        'account_id' => test()->account->id,
        ...$attributes,
    ]);
}

function summaryFor(string $month): array
{
    return app(MonthlySummary::class)->handle(test()->workspace, CarbonImmutable::parse($month));
}

test('an entry recorded today for a past date lands in the month it belongs to', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 40000,
        'due_on' => '2026-08-20',
        'settled_at' => now(),
    ]);

    $august = summaryFor('2026-08-01');
    $september = summaryFor('2026-09-01');

    expect($august['planned_expense_minor'])->toBe('40000')
        ->and($august['realized_balance_minor'])->toBe('-40000')
        ->and($august['forecast_balance_minor'])->toBe('-40000')
        ->and($september['opening_balance_minor'])->toBe('-40000')
        ->and($september['realized_balance_minor'])->toBe('-40000')
        ->and($september['forecast_balance_minor'])->toBe('-40000')
        ->and($september['planned_expense_minor'])->toBe('0');
});

test('an overdue pending entry stays out of realized balances but is carried into the current forecast', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 40000,
        'due_on' => '2026-08-20',
        'settled_at' => null,
    ]);

    $august = summaryFor('2026-08-01');
    $september = summaryFor('2026-09-01');

    expect($august['planned_expense_minor'])->toBe('40000')
        ->and($august['realized_balance_minor'])->toBe('0')
        ->and($august['forecast_balance_minor'])->toBe('0')
        ->and($september['opening_balance_minor'])->toBe('0')
        ->and($september['realized_balance_minor'])->toBe('0')
        ->and($september['forecast_balance_minor'])->toBe('-40000')
        ->and($september['planned_expense_minor'])->toBe('0');

    $october = summaryFor('2026-10-01');

    expect($october['opening_balance_minor'])->toBe('-40000')
        ->and($october['forecast_balance_minor'])->toBe('-40000');
});

test('marking an entry back as pending returns it to the forecast and out of the realized balance', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entry = movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 30000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);

    expect(summaryFor('2026-09-01')['realized_balance_minor'])->toBe('-30000');

    $response = $this->actingAs($this->user)
        ->patchJson(route('transactions.settlement', $entry), ['settled' => false])
        ->assertOk();

    expect($entry->fresh()->settled_at)->toBeNull()
        ->and($response->json('summary.realized_balance_minor'))->toBe('0')
        ->and($response->json('summary.forecast_balance_minor'))->toBe('-30000')
        ->and(app(AccountBalance::class)->handle($this->account->fresh()))->toBe('0');
});

test('deleting an entry removes it from every balance surface', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entry = movement([
        'category_id' => $this->incomeCategory->id,
        'type' => TransactionType::Income,
        'amount_minor' => 75000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->delete(route('transactions.destroy', $entry))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $balance = app(AccountBalance::class);
    $account = $this->account->fresh();
    $september = summaryFor('2026-09-01');

    expect($balance->handle($account))->toBe('0')
        ->and($balance->settledThrough($account, CarbonImmutable::parse('2026-09-30')))->toBe('0')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-10-31')))->toBe('0')
        ->and($september['planned_income_minor'])->toBe('0')
        ->and($september['realized_balance_minor'])->toBe('0')
        ->and($september['forecast_balance_minor'])->toBe('0')
        ->and($balance->currentAccounts($this->workspace)->firstWhere('id', $account->id)->getAttribute('balance_minor'))->toBe('0');
});

test('moving a pending entry to another month moves its whole forecast contribution', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entry = movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 90000,
        'due_on' => '2026-09-20',
        'settled_at' => null,
    ]);

    expect(summaryFor('2026-09-01')['forecast_balance_minor'])->toBe('-90000');

    $this->actingAs($this->user)->patch(route('transactions.update', $entry), [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 90000,
        'description' => $entry->description,
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-10-20',
        'settled' => false,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $september = summaryFor('2026-09-01');
    $october = summaryFor('2026-10-01');

    expect($september['planned_expense_minor'])->toBe('0')
        ->and($september['forecast_balance_minor'])->toBe('0')
        ->and($october['opening_balance_minor'])->toBe('0')
        ->and($october['planned_expense_minor'])->toBe('90000')
        ->and($october['forecast_balance_minor'])->toBe('-90000');
});

test('moving a settled entry to another month re-attributes it without moving the money back', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entry = movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 90000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);

    $this->actingAs($this->user)->patch(route('transactions.update', $entry), [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 90000,
        'description' => $entry->description,
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-10-20',
        'settled' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $september = summaryFor('2026-09-01');
    $october = summaryFor('2026-10-01');

    expect($september['planned_expense_minor'])->toBe('0')
        ->and($september['realized_balance_minor'])->toBe('-90000')
        ->and($september['forecast_balance_minor'])->toBe('-90000')
        ->and($october['opening_balance_minor'])->toBe('-90000')
        ->and($october['planned_expense_minor'])->toBe('90000')
        ->and($october['forecast_balance_minor'])->toBe('-90000');
});

test('transfers move value between accounts and leave the workspace total untouched', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    movement([
        'category_id' => $this->incomeCategory->id,
        'type' => TransactionType::Income,
        'amount_minor' => 500000,
        'due_on' => '2026-09-01',
        'settled_at' => now(),
    ]);
    movement([
        'destination_account_id' => $this->destination->id,
        'category_id' => null,
        'type' => TransactionType::Transfer,
        'amount_minor' => 120000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);
    movement([
        'destination_account_id' => $this->destination->id,
        'category_id' => null,
        'type' => TransactionType::Transfer,
        'amount_minor' => 80000,
        'due_on' => '2026-09-25',
        'settled_at' => null,
    ]);

    $september = summaryFor('2026-09-01');
    $accounts = collect($september['account_balances'])->keyBy('id');

    expect($september['planned_income_minor'])->toBe('500000')
        ->and($september['planned_expense_minor'])->toBe('0')
        ->and($september['realized_balance_minor'])->toBe('500000')
        ->and($september['forecast_balance_minor'])->toBe('500000')
        ->and($accounts[$this->account->id]['realized_balance_minor'])->toBe('380000')
        ->and($accounts[$this->destination->id]['realized_balance_minor'])->toBe('120000')
        ->and($accounts[$this->account->id]['forecast_balance_minor'])->toBe('300000')
        ->and($accounts[$this->destination->id]['forecast_balance_minor'])->toBe('200000');

    expect(MinorAmount::add(
        $accounts[$this->account->id]['forecast_balance_minor'],
        $accounts[$this->destination->id]['forecast_balance_minor'],
    ))->toBe($september['forecast_balance_minor']);
});

test('an installment purchase leaves the account by exactly its total across the series', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    app(CreateTransactions::class)->handle($this->workspace, [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 10000,
        'description' => 'Cadeira',
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-09-20',
        'settled' => false,
        'series_kind' => SeriesKind::Installment->value,
        'installments' => 3,
    ]);

    $balance = app(AccountBalance::class);
    $account = $this->account->fresh();

    expect(summaryFor('2026-09-01')['planned_expense_minor'])->toBe('3334')
        ->and(summaryFor('2026-10-01')['planned_expense_minor'])->toBe('3333')
        ->and(summaryFor('2026-11-01')['planned_expense_minor'])->toBe('3333')
        ->and(summaryFor('2026-12-01')['planned_expense_minor'])->toBe('0');

    expect($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-30')))->toBe('-3334')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-10-31')))->toBe('-6667')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-11-30')))->toBe('-10000')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2027-06-30')))->toBe('-10000');
});

test('a monthly recurrence adds exactly one occurrence to each month it spans', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    app(CreateTransactions::class)->handle($this->workspace, [
        'type' => TransactionType::Income->value,
        'amount_minor' => 250000,
        'description' => 'Mensalidade',
        'account_id' => $this->account->id,
        'category_id' => $this->incomeCategory->id,
        'due_on' => '2026-09-20',
        'settled' => false,
        'series_kind' => SeriesKind::Recurring->value,
        'frequency' => RecurrenceFrequency::Monthly->value,
    ]);

    $months = ['2026-09-01' => '250000', '2026-10-01' => '500000', '2026-11-01' => '750000', '2026-12-01' => '1000000'];
    $previousClosing = '0';

    foreach ($months as $month => $expectedClosing) {
        $summary = summaryFor($month);

        expect($summary['planned_income_minor'])->toBe('250000')
            ->and($summary['opening_balance_minor'])->toBe($previousClosing)
            ->and($summary['forecast_balance_minor'])->toBe($expectedClosing);

        $previousClosing = $summary['forecast_balance_minor'];
    }
});

test('closing future occurrences of a recurrence removes them from later forecasts', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    app(CreateTransactions::class)->handle($this->workspace, [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 60000,
        'description' => 'Assinatura',
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-09-20',
        'settled' => false,
        'series_kind' => SeriesKind::Recurring->value,
        'frequency' => RecurrenceFrequency::Monthly->value,
    ]);

    $november = Transaction::query()->whereDate('due_on', '2026-11-20')->sole();

    $this->actingAs($this->user)
        ->delete(route('transactions.destroy', $november), ['scope' => 'future'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->artisan('financeiro:generate-recurring')->assertSuccessful();

    expect(summaryFor('2026-09-01')['forecast_balance_minor'])->toBe('-60000')
        ->and(summaryFor('2026-10-01')['forecast_balance_minor'])->toBe('-120000')
        ->and(summaryFor('2026-11-01')['planned_expense_minor'])->toBe('0')
        ->and(summaryFor('2026-11-01')['forecast_balance_minor'])->toBe('-120000')
        ->and(summaryFor('2027-03-01')['forecast_balance_minor'])->toBe('-120000');
});

test('a dated opening balance ignores earlier movements and still projects later ones', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));
    $this->account->update([
        'initial_balance_minor' => 100000,
        'balance_date' => '2026-09-20',
    ]);

    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 7000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);
    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 5000,
        'due_on' => '2026-09-25',
        'settled_at' => now(),
    ]);
    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 3000,
        'due_on' => '2026-09-28',
        'settled_at' => null,
    ]);

    $balance = app(AccountBalance::class);
    $account = $this->account->fresh();

    expect($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-19')))->toBe('0')
        ->and($balance->settledThrough($account, CarbonImmutable::parse('2026-09-19')))->toBe('0')
        ->and($balance->settledThrough($account, CarbonImmutable::parse('2026-09-20')))->toBe('95000')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-30')))->toBe('92000');
});

test('paying next month bill in advance moves the money now and keeps the month attribution', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    movement([
        'category_id' => $this->incomeCategory->id,
        'type' => TransactionType::Income,
        'amount_minor' => 500000,
        'due_on' => '2026-09-01',
        'settled_at' => now(),
    ]);
    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 120000,
        'due_on' => '2026-10-10',
        'settled_at' => now(),
    ]);

    $september = summaryFor('2026-09-01');
    $october = summaryFor('2026-10-01');

    expect($september['realized_balance_minor'])->toBe('380000')
        ->and($september['forecast_balance_minor'])->toBe('380000')
        ->and($september['planned_expense_minor'])->toBe('0')
        ->and($october['opening_balance_minor'])->toBe('380000')
        ->and($october['planned_expense_minor'])->toBe('120000')
        ->and($october['forecast_balance_minor'])->toBe('380000')
        ->and($october['forecast_change_minor'])->toBe('0');
});

test('editing the amount of a settled entry restates every position', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entry = movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 45000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);

    $this->actingAs($this->user)->patch(route('transactions.update', $entry), [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 47550,
        'description' => $entry->description,
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-09-10',
        'settled' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $september = summaryFor('2026-09-01');

    expect($september['planned_expense_minor'])->toBe('47550')
        ->and($september['realized_balance_minor'])->toBe('-47550')
        ->and($september['forecast_balance_minor'])->toBe('-47550')
        ->and(app(AccountBalance::class)->handle($this->account->fresh()))->toBe('-47550');
});

test('reassigning an entry to another account moves the money with it', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entry = movement([
        'category_id' => $this->incomeCategory->id,
        'type' => TransactionType::Income,
        'amount_minor' => 65000,
        'due_on' => '2026-09-10',
        'settled_at' => now(),
    ]);

    $this->actingAs($this->user)->patch(route('transactions.update', $entry), [
        'type' => TransactionType::Income->value,
        'amount_minor' => 65000,
        'description' => $entry->description,
        'account_id' => $this->destination->id,
        'category_id' => $this->incomeCategory->id,
        'due_on' => '2026-09-10',
        'settled' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $september = summaryFor('2026-09-01');
    $accounts = collect($september['account_balances'])->keyBy('id');

    expect($september['realized_balance_minor'])->toBe('65000')
        ->and($accounts[$this->account->id]['realized_balance_minor'])->toBe('0')
        ->and($accounts[$this->destination->id]['realized_balance_minor'])->toBe('65000');
});

test('an archived account keeps contributing its history to monthly positions', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-20 09:00:00', 'America/Sao_Paulo'));

    movement([
        'category_id' => $this->incomeCategory->id,
        'type' => TransactionType::Income,
        'amount_minor' => 90000,
        'due_on' => '2026-08-10',
        'settled_at' => '2026-08-10 12:00:00',
    ]);
    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 90000,
        'due_on' => '2026-08-11',
        'settled_at' => '2026-08-11 12:00:00',
    ]);
    $this->account->update(['is_archived' => true]);

    $august = summaryFor('2026-08-01');
    $septemberAccounts = collect(summaryFor('2026-09-01')['account_balances'])->keyBy('id');
    $currentIds = app(AccountBalance::class)->currentAccounts($this->workspace)->pluck('id');

    expect($august['planned_income_minor'])->toBe('90000')
        ->and($august['planned_expense_minor'])->toBe('90000')
        ->and($august['realized_balance_minor'])->toBe('0')
        ->and($septemberAccounts->has($this->account->id))->toBeFalse()
        ->and($currentIds->contains($this->account->id))->toBeFalse();
});

test('monthly closings chain into the next opening across a long horizon', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));

    $entries = [
        [TransactionType::Income, 300000, '2026-08-05', true],
        [TransactionType::Expense, 45000, '2026-08-25', false],
        [TransactionType::Income, 12345, '2026-09-14', true],
        [TransactionType::Expense, 67891, '2026-09-28', false],
        [TransactionType::Expense, 1000, '2026-10-02', false],
        [TransactionType::Income, 999999, '2026-11-30', false],
        [TransactionType::Expense, 55555, '2026-12-31', false],
    ];

    foreach ($entries as [$type, $amount, $dueOn, $settled]) {
        movement([
            'category_id' => $type === TransactionType::Income ? $this->incomeCategory->id : $this->expenseCategory->id,
            'type' => $type,
            'amount_minor' => $amount,
            'due_on' => $dueOn,
            'settled_at' => $settled ? CarbonImmutable::parse($dueOn.' 12:00:00') : null,
        ]);
    }

    $months = ['2026-08-01', '2026-09-01', '2026-10-01', '2026-11-01', '2026-12-01', '2027-01-01'];
    $previous = null;

    foreach ($months as $month) {
        $summary = summaryFor($month);

        if ($previous !== null) {
            expect($summary['opening_balance_minor'])->toBe($previous['forecast_balance_minor']);
        }

        expect(MinorAmount::add($summary['opening_balance_minor'], $summary['forecast_change_minor']))
            ->toBe($summary['forecast_balance_minor']);

        $previous = $summary;
    }

    expect($previous['forecast_balance_minor'])->toBe('1142898');
});

test('a movement dated exactly on the opening balance date counts on top of it', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));
    $this->account->update([
        'initial_balance_minor' => 250000,
        'balance_date' => '2026-09-05',
    ]);

    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 25000,
        'due_on' => '2026-09-05',
        'settled_at' => '2026-09-05 12:00:00',
    ]);
    movement([
        'category_id' => $this->expenseCategory->id,
        'type' => TransactionType::Expense,
        'amount_minor' => 99999,
        'due_on' => '2026-09-04',
        'settled_at' => '2026-09-04 12:00:00',
    ]);

    $balance = app(AccountBalance::class);
    $account = $this->account->fresh();

    expect($balance->settledThrough($account, CarbonImmutable::parse('2026-09-05')))->toBe('225000')
        ->and($balance->handle($account))->toBe('225000')
        ->and($balance->projectedThrough($account, CarbonImmutable::parse('2026-09-30')))->toBe('225000');
});

test('a transfer into an account with a dated opening balance is not double counted', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-15 09:00:00', 'America/Sao_Paulo'));
    $this->destination->update([
        'initial_balance_minor' => 400000,
        'balance_date' => '2026-09-10',
    ]);

    movement([
        'destination_account_id' => $this->destination->id,
        'category_id' => null,
        'type' => TransactionType::Transfer,
        'amount_minor' => 30000,
        'due_on' => '2026-09-05',
        'settled_at' => '2026-09-05 12:00:00',
    ]);
    movement([
        'destination_account_id' => $this->destination->id,
        'category_id' => null,
        'type' => TransactionType::Transfer,
        'amount_minor' => 50000,
        'due_on' => '2026-09-12',
        'settled_at' => '2026-09-12 12:00:00',
    ]);

    $accounts = collect(summaryFor('2026-09-01')['account_balances'])->keyBy('id');

    expect($accounts[$this->destination->id]['realized_balance_minor'])->toBe('450000')
        ->and($accounts[$this->account->id]['realized_balance_minor'])->toBe('-80000');
});
