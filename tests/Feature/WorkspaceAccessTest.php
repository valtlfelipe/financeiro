<?php

use App\MembershipRole;
use App\Models\Invitation;
use App\Models\User;

test('only the owner can create invitations', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);

    $this->actingAs($member)->post(route('invitations.store'), ['email' => 'new@example.com'])->assertForbidden();
    $this->actingAs($owner)->post(route('invitations.store'), ['email' => 'new@example.com'])->assertRedirect();
    expect(Invitation::query()->sole()->email)->toBe('new@example.com');
});

test('a valid invitation can create a member and cannot be reused', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $token = str_repeat('a', 64);
    Invitation::factory()->create(['workspace_id' => $workspace->id, 'invited_by' => $owner->id, 'email' => 'member@example.com', 'token_hash' => hash('sha256', $token)]);

    auth()->logout();
    $this->post(route('invitations.accept', $token), ['name' => 'Membro', 'password' => 'senha-segura-123', 'password_confirmation' => 'senha-segura-123'])->assertRedirect(route('dashboard'));

    $member = User::query()->where('email', 'member@example.com')->firstOrFail();
    expect($member->workspaces()->whereKey($workspace->id)->exists())->toBeTrue();

    auth()->logout();
    $this->post(route('invitations.accept', $token), ['password' => 'senha-segura-123'])->assertForbidden();
});
