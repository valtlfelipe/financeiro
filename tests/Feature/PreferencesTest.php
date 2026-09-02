<?php

use App\MembershipRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('owners can rename their current workspace without changing other settings', function () {
    [$user, $workspace] = ownerWithWorkspace();
    [, $otherWorkspace] = ownerWithWorkspace();

    $this->actingAs($user)->patch(route('preferences.update'), [
        'workspace_name' => 'Casa e família',
        'workspace_id' => $otherWorkspace->id,
        'currency_code' => 'USD',
        'locale' => 'xx',
    ])->assertSessionHasNoErrors()->assertRedirect(route('preferences.edit'));

    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => 'Casa e família', 'currency_code' => $workspace->currency_code]);
    $this->assertDatabaseHas('workspaces', ['id' => $otherWorkspace->id, 'name' => $otherWorkspace->name]);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'locale' => $user->locale]);
    $this->actingAs($user->fresh())->get(route('preferences.edit'))->assertInertia(fn (Assert $page) => $page
        ->component('settings/Preferences')
        ->where('workspace.name', 'Casa e família')
        ->where('locale', $user->locale)
        ->has('supportedLocales'));
});

test('members may change language but may not rename the shared workspace', function () {
    [, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);

    $this->actingAs($member)->patch(route('preferences.update'), ['workspace_name' => 'Nome proibido'])->assertForbidden();

    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => $workspace->name]);
    $this->patch(route('locale.update'), ['locale' => 'pt-BR'])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $member->id, 'locale' => 'pt-BR']);
});

test('invalid workspace names leave the workspace unchanged', function (mixed $name) {
    [$user, $workspace] = ownerWithWorkspace();

    $this->actingAs($user)->patch(route('preferences.update'), ['workspace_name' => $name])
        ->assertSessionHasErrors('workspace_name');

    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => $workspace->name]);
})->with(['empty' => '', 'whitespace' => '   ', 'long' => str_repeat('a', 121), 'array' => [['invalid']]]);

test('guests and non-members cannot rename a workspace', function () {
    [$user, $workspace] = ownerWithWorkspace();
    $this->patch(route('preferences.update'), ['workspace_name' => 'Proibido'])->assertRedirect(route('login'));
    $workspace->users()->detach($user);

    $this->actingAs($user)->patch(route('preferences.update'), ['workspace_name' => 'Proibido'])->assertForbidden();

    $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'name' => $workspace->name]);
});
