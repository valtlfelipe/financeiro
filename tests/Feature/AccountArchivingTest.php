<?php

use App\Models\Account;
use App\Models\Transaction;
use Inertia\Testing\AssertableInertia as Assert;

test('archiving an account keeps its transactions and removes it from new transaction options', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $transaction = Transaction::factory()->for($workspace)->for($account)->create(['due_on' => '2026-09-10']);

    $this->actingAs($user)->from(route('accounts.index'))->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_archived' => true]);
    $this->assertNotSoftDeleted($transaction);
    $this->get(route('transactions.index', ['month' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('accounts', 0)
            ->where('transactions.0.account.id', $account->id));
});

test('an account from another workspace cannot be archived', function () {
    [$user] = ownerWithWorkspace();
    $account = Account::factory()->create();

    $this->actingAs($user)->delete(route('accounts.destroy', $account))->assertNotFound();

    $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_archived' => false]);
});
