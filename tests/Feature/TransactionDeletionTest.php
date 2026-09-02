<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionSeries;

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

test('another workspace cannot delete a transaction', function () {
    [$user] = ownerWithWorkspace();
    $transaction = Transaction::factory()->create();

    $this->actingAs($user)->delete(route('transactions.destroy', $transaction), ['scope' => 'single'])
        ->assertNotFound();

    $this->assertNotSoftDeleted($transaction);
});
