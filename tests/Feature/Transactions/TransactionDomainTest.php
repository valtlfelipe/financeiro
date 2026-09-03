<?php

use App\Actions\Transactions\CreateTransactions;
use App\Actions\Transactions\GenerateSeriesOccurrences;
use App\Actions\Transactions\MonthlySummary;
use App\CategoryType;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionSeries;
use App\RecurrenceFrequency;
use App\SeriesKind;
use App\TransactionType;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    [$this->user, $this->workspace] = ownerWithWorkspace();
    $this->account = Account::factory()->create(['workspace_id' => $this->workspace->id]);
    $this->destination = Account::factory()->create(['workspace_id' => $this->workspace->id]);
    $this->expenseCategory = Category::factory()->create(['workspace_id' => $this->workspace->id, 'type' => CategoryType::Expense]);
    $this->incomeCategory = Category::factory()->create(['workspace_id' => $this->workspace->id, 'type' => CategoryType::Income]);
});

test('copying transaction data creates a separate entry without changing the original', function () {
    $original = Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'description' => 'Aluguel original',
        'type' => TransactionType::Expense,
        'amount_minor' => 85000,
        'due_on' => '2026-09-10',
    ]);

    $this->actingAs($this->user)->post(route('transactions.store'), [
        'description' => 'Aluguel copiado',
        'type' => $original->type->value,
        'amount_minor' => $original->amount_minor,
        'account_id' => $original->account_id,
        'category_id' => $original->category_id,
        'due_on' => '2026-10-10',
        'settled' => false,
        'series_kind' => null,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('transactions', 2);
    $this->assertDatabaseHas('transactions', [
        'id' => $original->id,
        'description' => 'Aluguel original',
        'amount_minor' => 85000,
    ]);
    $this->assertDatabaseHas('transactions', [
        'description' => 'Aluguel copiado',
        'amount_minor' => 85000,
        'settled_at' => null,
        'transaction_series_id' => null,
    ]);
    expect($original->fresh()->due_on->format('Y-m-d'))->toBe('2026-09-10');
    expect(Transaction::query()->where('description', 'Aluguel copiado')->sole()->due_on->format('Y-m-d'))->toBe('2026-10-10');
});

test('money and monthly totals remain integer cents and transfers are excluded', function () {
    Transaction::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'category_id' => $this->incomeCategory->id, 'type' => TransactionType::Income, 'amount_minor' => 10001, 'due_on' => '2026-09-03', 'settled_at' => now()]);
    Transaction::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'category_id' => $this->expenseCategory->id, 'type' => TransactionType::Expense, 'amount_minor' => 3334, 'due_on' => '2026-09-04']);
    Transaction::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'destination_account_id' => $this->destination->id, 'category_id' => null, 'type' => TransactionType::Transfer, 'amount_minor' => 999999, 'due_on' => '2026-09-05', 'settled_at' => now()]);
    $deleted = Transaction::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'category_id' => $this->incomeCategory->id, 'type' => TransactionType::Income, 'amount_minor' => 500000, 'due_on' => '2026-09-06', 'settled_at' => now()]);
    $deleted->delete();
    Transaction::factory()->create(['type' => TransactionType::Income, 'amount_minor' => 700000, 'due_on' => '2026-09-07', 'settled_at' => now()]);

    expect(app(MonthlySummary::class)->handle($this->workspace, CarbonImmutable::parse('2026-09-01')))->toBe([
        'planned_income_minor' => 10001,
        'planned_expense_minor' => 3334,
        'realized_balance_minor' => 10001,
        'forecast_balance_minor' => 6667,
    ]);
});

test('transaction resources keep nullable relations as null', function () {
    $transaction = Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'account_id' => $this->account->id,
        'destination_account_id' => $this->destination->id,
        'category_id' => null,
        'type' => TransactionType::Transfer,
    ]);

    $payload = json_decode(
        json_encode(TransactionResource::make(
            $transaction->load(['account', 'destinationAccount', 'category', 'series']),
        )->resolve(), JSON_THROW_ON_ERROR),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($payload['destinationAccount'])->toBeArray()
        ->and($payload['category'])->toBeNull();
});

test('transaction pages serialize nested account and category data for inertia', function () {
    Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'account_id' => $this->account->id,
        'category_id' => $this->incomeCategory->id,
        'type' => TransactionType::Income,
        'description' => 'Receita serializada',
        'due_on' => '2026-09-02',
    ]);

    $response = $this->actingAs($this->user)->get(route('transactions.index', [
        'month' => '2026-09',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Transactions/Index')
        ->where('transactions.0.account.name', $this->account->name)
        ->where('transactions.0.category.name', $this->incomeCategory->name)
        ->where('transactions.0.destinationAccount', null));
});

test('installments distribute remainder cents exactly and are idempotent', function () {
    $first = app(CreateTransactions::class)->handle($this->workspace, [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 10000,
        'description' => 'Curso',
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-01-31',
        'series_kind' => SeriesKind::Installment->value,
        'installments' => 3,
    ]);

    $series = $first->series;
    expect($series)->not->toBeNull()
        ->and($series->transactions()->orderBy('installment_number')->pluck('amount_minor')->all())->toBe([3334, 3333, 3333])
        ->and($series->transactions()->orderBy('installment_number')->pluck('due_on')->map->format('Y-m-d')->all())->toBe(['2026-01-31', '2026-02-28', '2026-03-31'])
        ->and($series->transactions()->sum('amount_minor'))->toBe(10000);

    expect(app(GenerateSeriesOccurrences::class)->handle($series))->toBe(0)
        ->and($series->transactions()->count())->toBe(3);
});

test('monthly and yearly recurrences clamp calendar boundaries without drifting', function () {
    $monthly = TransactionSeries::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'category_id' => $this->expenseCategory->id, 'starts_on' => '2024-01-31', 'ends_on' => '2024-03-31', 'frequency' => RecurrenceFrequency::Monthly]);
    app(GenerateSeriesOccurrences::class)->handle($monthly, CarbonImmutable::parse('2024-04-01'));

    $yearly = TransactionSeries::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'category_id' => $this->expenseCategory->id, 'starts_on' => '2024-02-29', 'ends_on' => '2026-03-01', 'frequency' => RecurrenceFrequency::Yearly]);
    app(GenerateSeriesOccurrences::class)->handle($yearly, CarbonImmutable::parse('2026-03-01'));

    expect($monthly->transactions()->orderBy('due_on')->pluck('due_on')->map->format('Y-m-d')->all())->toBe(['2024-01-31', '2024-02-29', '2024-03-31'])
        ->and($yearly->transactions()->orderBy('due_on')->pluck('due_on')->map->format('Y-m-d')->all())->toBe(['2024-02-29', '2025-02-28', '2026-02-28']);
});

test('transaction endpoints enforce workspace isolation and settlement returns updated totals', function () {
    [, $otherWorkspace] = ownerWithWorkspace();
    $foreignAccount = Account::factory()->create(['workspace_id' => $otherWorkspace->id]);
    $foreignTransaction = Transaction::factory()->create(['workspace_id' => $otherWorkspace->id, 'account_id' => $foreignAccount->id]);

    $this->actingAs($this->user)->patchJson(route('transactions.settlement', $foreignTransaction), ['settled' => true])->assertNotFound();

    $transaction = Transaction::factory()->create(['workspace_id' => $this->workspace->id, 'account_id' => $this->account->id, 'category_id' => $this->expenseCategory->id, 'type' => TransactionType::Expense, 'amount_minor' => 1234, 'due_on' => '2026-09-10']);
    $this->actingAs($this->user)->patchJson(route('transactions.settlement', $transaction), ['settled' => true])
        ->assertOk()->assertJsonPath('transaction.settledAt', fn ($value) => is_string($value))->assertJsonPath('summary.realized_balance_minor', -1234);
});

test('transaction validation rejects mismatched categories and cross-workspace accounts', function () {
    [, $otherWorkspace] = ownerWithWorkspace();
    $foreignAccount = Account::factory()->create(['workspace_id' => $otherWorkspace->id]);

    $this->actingAs($this->user)->post(route('transactions.store'), [
        'type' => TransactionType::Expense->value,
        'amount_minor' => 100,
        'description' => 'Inválido',
        'account_id' => $foreignAccount->id,
        'category_id' => $this->incomeCategory->id,
        'due_on' => '2026-09-01',
    ])->assertSessionHasErrors(['account_id', 'category_id']);
});

test('income and expense transactions require an explicit category', function (TransactionType $type) {
    $response = $this->actingAs($this->user)
        ->from(route('transactions.index'))
        ->post(route('transactions.store'), [
            'type' => $type->value,
            'amount_minor' => 5050,
            'description' => 'Sem categoria',
            'account_id' => $this->account->id,
            'category_id' => null,
            'due_on' => '2026-09-02',
        ]);

    $response->assertRedirect(route('transactions.index'))->assertSessionHasErrors(['category_id']);
    $this->assertDatabaseCount('transactions', 0);
})->with([
    'income' => TransactionType::Income,
    'expense' => TransactionType::Expense,
]);

test('a valid transfer is persisted between two accounts without a category', function () {
    $response = $this->actingAs($this->user)
        ->from(route('transactions.index'))
        ->post(route('transactions.store'), [
            'type' => TransactionType::Transfer->value,
            'amount_minor' => 5050,
            'description' => 'Reserva mensal',
            'account_id' => $this->account->id,
            'destination_account_id' => $this->destination->id,
            'due_on' => '2026-09-02',
        ]);

    $response->assertRedirect(route('transactions.index'))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('transactions', [
        'workspace_id' => $this->workspace->id,
        'type' => TransactionType::Transfer->value,
        'amount_minor' => 5050,
        'description' => 'Reserva mensal',
        'account_id' => $this->account->id,
        'destination_account_id' => $this->destination->id,
        'category_id' => null,
    ]);
});

test('the settlement choice persists when creating a transaction', function (bool $settled) {
    $this->travelTo(CarbonImmutable::parse('2026-09-02 12:00:00'));

    $this->actingAs($this->user)->post(route('transactions.store'), [
        'type' => 'expense',
        'amount_minor' => 1234,
        'description' => 'Estado do pagamento',
        'account_id' => $this->account->id,
        'category_id' => $this->expenseCategory->id,
        'due_on' => '2026-09-02',
        'settled' => $settled,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseHas('transactions', [
        'description' => 'Estado do pagamento',
        'settled_at' => $settled ? now()->toDateTimeString() : null,
    ]);
})->with(['pending' => false, 'settled' => true]);
