<?php

use App\Models\Transaction;
use App\Models\TransactionSeries;
use App\SeriesKind;
use App\TransactionType;
use Illuminate\Database\QueryException;

test('the database rejects non-positive transaction amounts', function () {
    $transaction = Transaction::factory()->create();

    expect(fn () => $transaction->update(['amount_minor' => 0]))
        ->toThrow(QueryException::class);
});

test('the database rejects invalid transfer shapes', function () {
    $transaction = Transaction::factory()->create();

    expect(fn () => $transaction->update([
        'type' => TransactionType::Transfer,
        'destination_account_id' => null,
    ]))->toThrow(QueryException::class);
});

test('the database rejects incomplete installment metadata', function () {
    $transaction = Transaction::factory()->create();

    expect(fn () => $transaction->update([
        'installment_number' => 1,
        'installment_total' => null,
    ]))->toThrow(QueryException::class);
});

test('the database rejects installment series that would contain zero amounts', function () {
    $series = TransactionSeries::factory()->create();

    expect(fn () => $series->update([
        'kind' => SeriesKind::Installment,
        'frequency' => null,
        'total_occurrences' => 2,
        'amount_minor' => 1,
    ]))->toThrow(QueryException::class);
});
