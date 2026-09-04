<?php

use App\Actions\Transactions\GenerateSeriesOccurrences;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionSeries;
use App\RecurrenceFrequency;
use App\SeriesKind;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Exceptions;

test('deleting a single transaction preserves other occurrences', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $transactions = Transaction::factory()->for($workspace)->for($account)->count(2)->create();

    $this->actingAs($user)->from(route('transactions.index'))
        ->delete(route('transactions.destroy', $transactions[0]), ['scope' => 'single'])
        ->assertRedirect(route('transactions.index'));

    $this->assertSoftDeleted($transactions[0]);
    $this->assertNotSoftDeleted($transactions[1]);
});

test('future deletion preserves prior and already settled occurrences', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create();
    $transactions = Transaction::factory()->for($workspace)->for($account)->for($series, 'series')
        ->count(4)->sequence(
            ['due_on' => '2026-08-01'],
            ['due_on' => '2026-09-01'],
            ['due_on' => '2026-10-01'],
            ['due_on' => '2026-11-01', 'settled_at' => '2026-09-01 12:00:00'],
        )->create();

    $this->actingAs($user)->delete(route('transactions.destroy', $transactions[1]), ['scope' => 'future'])
        ->assertRedirect();

    $this->assertSoftDeleted($transactions[1]);
    $this->assertSoftDeleted($transactions[2]);
    $this->assertNotSoftDeleted($transactions[0]);
    $this->assertNotSoftDeleted($transactions[3]);
});

test('a deleted recurring occurrence is not recreated', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create([
        'starts_on' => '2026-09-01',
        'ends_on' => '2026-11-01',
    ]);
    $generator = app(GenerateSeriesOccurrences::class);

    expect($generator->handle($series, CarbonImmutable::parse('2026-11-01')))->toBe(3);
    $transaction = $series->transactions()->whereDate('due_on', '2026-10-01')->firstOrFail();

    $this->actingAs($user)->delete(route('transactions.destroy', $transaction), ['scope' => 'single'])
        ->assertRedirect();

    expect($generator->handle($series->fresh(), CarbonImmutable::parse('2026-11-01')))->toBe(0);
    $this->assertSoftDeleted($transaction);
    expect($series->transactions()->withTrashed()->count())->toBe(3);
});

test('future deletion closes a recurring series and prevents regeneration', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create([
        'starts_on' => '2026-09-01',
        'ends_on' => null,
    ]);
    $generator = app(GenerateSeriesOccurrences::class);
    $generator->handle($series, CarbonImmutable::parse('2026-12-01'));
    $transaction = $series->transactions()->whereDate('due_on', '2026-10-01')->firstOrFail();

    $this->actingAs($user)->delete(route('transactions.destroy', $transaction), ['scope' => 'future'])
        ->assertRedirect();

    expect($series->fresh()->ends_on->toDateString())->toBe('2026-09-30');
    expect($generator->handle($series->fresh(), CarbonImmutable::parse('2027-12-01')))->toBe(0);
    expect($series->transactions()->withTrashed()->count())->toBe(4);
    expect($series->transactions()->count())->toBe(1);
});

test('recurring generation continues after a series fails', function () {
    Exceptions::fake();
    $firstSeries = TransactionSeries::factory()->create([
        'kind' => SeriesKind::Recurring,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => today(),
    ]);
    $secondSeries = TransactionSeries::factory()->create([
        'kind' => SeriesKind::Recurring,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => today(),
    ]);
    $processedSeries = [];
    $generator = Mockery::mock(GenerateSeriesOccurrences::class);
    $generator->shouldReceive('handle')->twice()->andReturnUsing(
        function (TransactionSeries $series) use (&$processedSeries, $firstSeries): int {
            $processedSeries[] = $series->id;

            if ($series->is($firstSeries)) {
                throw new RuntimeException('Broken recurring series.');
            }

            return 1;
        },
    );
    $this->app->instance(GenerateSeriesOccurrences::class, $generator);

    $this->artisan('financeiro:generate-recurring')->assertFailed();

    expect($processedSeries)->toBe([$firstSeries->id, $secondSeries->id]);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Broken recurring series.');
});

test('deleting a series occurrence requires an explicit scope', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $series = TransactionSeries::factory()->for($workspace)->for($account)->create();
    $transaction = Transaction::factory()->for($workspace)->for($account)->for($series, 'series')->create();

    $this->actingAs($user)->delete(route('transactions.destroy', $transaction))
        ->assertSessionHasErrors('scope');

    $this->assertNotSoftDeleted($transaction);
});

test('another workspace cannot delete a transaction', function () {
    [$user] = ownerWithWorkspace();
    $transaction = Transaction::factory()->create();

    $this->actingAs($user)->delete(route('transactions.destroy', $transaction), ['scope' => 'single'])
        ->assertNotFound();

    $this->assertNotSoftDeleted($transaction);
});
