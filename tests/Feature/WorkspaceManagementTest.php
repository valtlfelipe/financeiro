<?php

use App\CurrentWorkspace;
use App\MembershipRole;
use App\Models\Account;
use App\Models\Membership;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;

test('an owner can create and select a workspace with the default financial structure', function () {
    [$owner, $ownedWorkspace] = ownerWithWorkspace();
    $sharedWorkspace = Workspace::factory()->create(['name' => 'Compartilhado']);
    Membership::factory()->create([
        'workspace_id' => $sharedWorkspace->id,
        'user_id' => $owner->id,
        'role' => MembershipRole::Member,
    ]);

    $response = $this->actingAs($owner)
        ->withSession([CurrentWorkspace::SESSION_KEY => $sharedWorkspace->id])
        ->post(route('workspaces.store'), ['workspace_name' => 'Empresa']);

    $response->assertRedirect(route('dashboard'));

    $workspace = Workspace::query()->where('name', 'Empresa')->firstOrFail();
    expect($workspace->currency_code)->toBe('BRL')
        ->and($workspace->timezone)->toBe('America/Sao_Paulo')
        ->and($workspace->accounts)->toHaveCount(1)
        ->and($workspace->accounts->sole()->initial_balance_minor)->toBe(0)
        ->and($workspace->categories)->toHaveCount(2)
        ->and($owner->refresh()->current_workspace_id)->toBe($workspace->id);
    $this->assertDatabaseHas('memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $owner->id,
        'role' => MembershipRole::Owner->value,
    ]);
    $response->assertSessionHas(CurrentWorkspace::SESSION_KEY, $workspace->id);
    expect($workspace->id)->not->toBeIn([$ownedWorkspace->id, $sharedWorkspace->id]);
});

test('members who do not own any workspace cannot create one', function () {
    [, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    Membership::factory()->create([
        'workspace_id' => $workspace->id,
        'user_id' => $member->id,
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($member)
        ->post(route('workspaces.store'), ['workspace_name' => 'Negado'])
        ->assertForbidden();

    $this->assertDatabaseMissing('workspaces', ['name' => 'Negado']);
});

test('workspace creation validates the name', function (array $payload) {
    [$owner] = ownerWithWorkspace();

    $this->actingAs($owner)
        ->post(route('workspaces.store'), $payload)
        ->assertSessionHasErrors('workspace_name');
})->with([
    'missing' => [[]],
    'too long' => [['workspace_name' => str_repeat('a', 121)]],
]);

test('a user can switch to any workspace they belong to', function (MembershipRole $role) {
    [$user, $currentWorkspace] = ownerWithWorkspace();
    $targetWorkspace = Workspace::factory()->create(['name' => 'Destino']);
    Membership::factory()->create([
        'workspace_id' => $targetWorkspace->id,
        'user_id' => $user->id,
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $currentWorkspace->id])
        ->patch(route('workspaces.switch'), ['workspace_id' => $targetWorkspace->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(CurrentWorkspace::SESSION_KEY, $targetWorkspace->id);

    expect($user->refresh()->current_workspace_id)->toBe($targetWorkspace->id);
})->with(MembershipRole::cases());

test('switching to an inaccessible workspace returns not found and keeps the selection', function () {
    [$user, $currentWorkspace] = ownerWithWorkspace();
    [, $otherWorkspace] = ownerWithWorkspace();

    $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $currentWorkspace->id])
        ->patch(route('workspaces.switch'), ['workspace_id' => $otherWorkspace->id])
        ->assertNotFound()
        ->assertSessionHas(CurrentWorkspace::SESSION_KEY, $currentWorkspace->id);

    expect($user->refresh()->current_workspace_id)->toBe($currentWorkspace->id);
});

test('the session selection wins over the saved preference and drives shared props', function () {
    [$user, $savedWorkspace] = ownerWithWorkspace();
    $sessionWorkspace = Workspace::factory()->create(['name' => 'Nesta sessão']);
    Membership::factory()->create([
        'workspace_id' => $sessionWorkspace->id,
        'user_id' => $user->id,
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $sessionWorkspace->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('workspace.id', $sessionWorkspace->id)
            ->where('workspace.role', MembershipRole::Member->value)
            ->has('workspaces', 2)
            ->where('canCreateWorkspace', true));

    expect($user->refresh()->current_workspace_id)->toBe($savedWorkspace->id);
});

test('an inaccessible session selection falls back to the first available workspace', function () {
    [$user, $firstWorkspace] = ownerWithWorkspace();
    $secondWorkspace = Workspace::factory()->create();
    $secondWorkspace->addOwner($user);
    $removedWorkspace = Workspace::factory()->create();

    $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $removedWorkspace->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSessionHas(CurrentWorkspace::SESSION_KEY, $firstWorkspace->id);

    expect($user->refresh()->current_workspace_id)->toBe($firstWorkspace->id);
});

test('stale workspace mutations are rejected before changing data', function (?string $workspaceHeader) {
    [$user, $workspace] = ownerWithWorkspace();
    $request = $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $workspace->id]);

    if ($workspaceHeader === null) {
        $request->withoutHeader('X-Workspace-Id');
    } else {
        $request->withHeader('X-Workspace-Id', $workspaceHeader);
    }

    $request->post(route('accounts.store'), [
        'name' => 'Não deve existir',
        'type' => 'checking',
        'initial_balance_minor' => 0,
        'balance_date' => now()->toDateString(),
        'color' => '#148A62',
    ])->assertConflict()->assertHeader('X-Workspace-Context-Changed', 'true');

    expect(Account::query()->where('name', 'Não deve existir')->exists())->toBeFalse();
})->with([
    'missing header' => null,
    'different workspace' => '999999',
]);

test('workspace data is written to the session selection instead of the saved preference', function () {
    [$user, $savedWorkspace] = ownerWithWorkspace();
    $sessionWorkspace = Workspace::factory()->create();
    $sessionWorkspace->addOwner($user);

    $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $sessionWorkspace->id])
        ->withHeader('X-Workspace-Id', (string) $sessionWorkspace->id)
        ->post(route('accounts.store'), [
            'name' => 'Conta da sessão',
            'type' => 'checking',
            'initial_balance_minor' => 0,
            'balance_date' => now()->toDateString(),
            'color' => '#148A62',
        ])->assertRedirect();

    $this->assertDatabaseHas('accounts', [
        'workspace_id' => $sessionWorkspace->id,
        'name' => 'Conta da sessão',
    ]);
    $this->assertDatabaseMissing('accounts', [
        'workspace_id' => $savedWorkspace->id,
        'name' => 'Conta da sessão',
    ]);
});

test('a stale settlement request cannot update a transaction', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $transaction = Transaction::factory()->recycle($workspace)->create(['settled_at' => null]);

    $this->actingAs($user)
        ->withSession([CurrentWorkspace::SESSION_KEY => $workspace->id])
        ->withHeader('X-Workspace-Id', '999999')
        ->patchJson(route('transactions.settlement', $transaction), ['settled' => true])
        ->assertConflict()
        ->assertHeader('X-Workspace-Context-Changed', 'true');

    expect($transaction->refresh()->settled_at)->toBeNull();
});
