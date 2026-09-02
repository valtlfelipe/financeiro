<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Inertia\Testing\AssertableInertia as Assert;

test('archiving a category preserves classified transactions and removes it from new transaction options', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $account = Account::factory()->for($workspace)->create();
    $category = Category::factory()->for($workspace)->create();
    $transaction = Transaction::factory()->for($workspace)->for($account)->for($category)->create(['due_on' => '2026-09-10']);

    $this->actingAs($user)->from(route('categories.index'))->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_archived' => true]);
    $this->assertNotSoftDeleted($transaction);
    $this->get(route('transactions.index', ['month' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 0)
            ->where('transactions.0.category.id', $category->id));
});

test('a category from another workspace cannot be archived', function () {
    [$user] = ownerWithWorkspace();
    $category = Category::factory()->create();

    $this->actingAs($user)->delete(route('categories.destroy', $category))->assertNotFound();

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_archived' => false]);
});
