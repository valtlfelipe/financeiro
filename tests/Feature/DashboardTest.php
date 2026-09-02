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
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.description', 'Receita pendente')
            ->where('recentTransactions.0.dueOn', '2026-09-02')
            ->where('recentTransactions.0.account.name', $account->name)
            ->where('recentTransactions.0.category.name', $category->name));
    }
}
