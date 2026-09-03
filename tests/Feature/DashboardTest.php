<?php

namespace Tests\Feature;

use App\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        [$user] = ownerWithWorkspace();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_lists_only_pending_transactions_with_nested_relations(): void
    {
        [$user, $workspace] = ownerWithWorkspace();
        $account = Account::factory()->create(['workspace_id' => $workspace->id]);
        $category = Category::factory()->create([
            'workspace_id' => $workspace->id,
            'type' => CategoryType::Income,
        ]);
        Transaction::factory()->create([
            'workspace_id' => $workspace->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => TransactionType::Income,
            'description' => 'Receita pendente',
            'due_on' => '2026-09-02',
        ]);
        Transaction::factory()->create([
            'workspace_id' => $workspace->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => TransactionType::Income,
            'description' => 'Receita realizada',
            'due_on' => '2026-09-03',
            'settled_at' => '2026-09-03 12:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', ['month' => '2026-09']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('summary')
            ->has('accounts', 1)
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.description', 'Receita pendente')
            ->where('recentTransactions.0.dueOn', '2026-09-02')
            ->where('recentTransactions.0.account.name', $account->name)
            ->where('recentTransactions.0.category.name', $category->name));
    }

    public function test_account_balance_counts_settled_movements_from_the_opening_balance_date(): void
    {
        [$user, $workspace] = ownerWithWorkspace();
        $account = Account::factory()->for($workspace)->create([
            'initial_balance_minor' => 100000,
            'balance_date' => '2026-09-10',
        ]);
        $otherAccount = Account::factory()->for($workspace)->create();

        Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Income,
            'amount_minor' => 5000,
            'due_on' => '2026-09-10',
            'settled_at' => '2026-09-11 12:00:00',
        ]);
        Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Income,
            'amount_minor' => 25000,
            'due_on' => '2026-09-11',
            'settled_at' => '2026-09-11 12:00:00',
        ]);
        Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Expense,
            'amount_minor' => 3000,
            'due_on' => '2026-09-12',
            'settled_at' => '2026-09-12 12:00:00',
        ]);
        Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Income,
            'amount_minor' => 999999,
            'due_on' => '2026-09-13',
            'settled_at' => null,
        ]);
        Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Expense,
            'amount_minor' => 50000,
            'due_on' => '2026-09-09',
            'settled_at' => '2026-09-09 12:00:00',
        ]);
        $deleted = Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Income,
            'amount_minor' => 80000,
            'due_on' => '2026-09-14',
            'settled_at' => '2026-09-14 12:00:00',
        ]);
        $deleted->delete();
        Transaction::factory()->for($workspace)->for($account)->create([
            'destination_account_id' => $otherAccount->id,
            'type' => TransactionType::Transfer,
            'amount_minor' => 10000,
            'due_on' => '2026-09-15',
            'settled_at' => '2026-09-15 12:00:00',
        ]);
        Transaction::factory()->for($workspace)->for($otherAccount)->create([
            'destination_account_id' => $account->id,
            'type' => TransactionType::Transfer,
            'amount_minor' => 7000,
            'due_on' => '2026-09-16',
            'settled_at' => '2026-09-16 12:00:00',
        ]);

        $this->actingAs($user)->get(route('dashboard', ['month' => '2026-09']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('accounts.0.id', $account->id)
                ->where('accounts.0.balanceMinor', 124000));
    }

    public function test_account_balance_keeps_exact_negative_cent_values(): void
    {
        [$user, $workspace] = ownerWithWorkspace();
        $account = Account::factory()->for($workspace)->create([
            'initial_balance_minor' => 100,
            'balance_date' => '2026-09-10',
        ]);
        Transaction::factory()->for($workspace)->for($account)->create([
            'type' => TransactionType::Expense,
            'amount_minor' => 101,
            'due_on' => '2026-09-11',
            'settled_at' => '2026-09-11 12:00:00',
        ]);

        $this->actingAs($user)->get(route('dashboard', ['month' => '2026-09']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('accounts.0.balanceMinor', -1));
    }
}
