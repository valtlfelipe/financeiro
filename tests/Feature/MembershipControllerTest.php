<?php

use App\MembershipRole;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Transaction;
use App\Models\User;

test('removing a member revokes access and old invitations while keeping their account and transactions', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $membership = Membership::factory()->create([
        'workspace_id' => $workspace->id, 'user_id' => $member->id, 'role' => MembershipRole::Member,
    ]);
    $transaction = Transaction::factory()->recycle($workspace)->create(['workspace_id' => $workspace->id]);
    $token = str_repeat('r', 64);
    $oldInvitation = Invitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => strtoupper($member->email),
        'token_hash' => hash('sha256', $token),
    ]);

    $this->actingAs($owner)->delete(route('members.destroy', $member))->assertRedirect(route('invitations.index'));

    $this->assertModelMissing($membership);
    $this->assertModelMissing($oldInvitation);
    $this->assertModelExists($transaction);
    $this->assertModelExists($member);
    expect($member->refresh()->current_workspace_id)->toBeNull();
    $this->actingAs($member)->get(route('dashboard'))->assertForbidden();
    $this->get(route('invitations.show', $token))->assertNotFound();
});

test('removed members can use another workspace they belong to', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    [$member, $otherWorkspace] = ownerWithWorkspace();
    $member->update(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);

    $this->actingAs($owner)->delete(route('members.destroy', $member))->assertRedirect(route('invitations.index'));

    $this->assertDatabaseMissing('memberships', ['workspace_id' => $workspace->id, 'user_id' => $member->id]);
    $this->actingAs($member->refresh())->get(route('dashboard'))->assertOk();
    expect($member->refresh()->current_workspace_id)->toBe($otherWorkspace->id);
    $this->assertDatabaseHas('memberships', ['workspace_id' => $otherWorkspace->id, 'user_id' => $member->id, 'role' => 'owner']);
});

test('removal preserves the member current workspace and invitations in other spaces', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    [$member, $otherWorkspace] = ownerWithWorkspace();
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);
    $otherInvitation = Invitation::factory()->create([
        'workspace_id' => $otherWorkspace->id, 'invited_by' => $member->id, 'email' => $member->email,
    ]);

    $this->actingAs($owner)->delete(route('members.destroy', $member))->assertRedirect(route('invitations.index'));

    expect($member->refresh()->current_workspace_id)->toBe($otherWorkspace->id);
    $this->assertModelExists($otherInvitation);
    $this->assertDatabaseMissing('memberships', ['workspace_id' => $workspace->id, 'user_id' => $member->id]);
});

test('owners cannot be removed', function () {
    [$owner, $workspace] = ownerWithWorkspace();

    $this->actingAs($owner)->delete(route('members.destroy', $owner))->assertForbidden();

    $this->assertDatabaseHas('memberships', ['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);
    expect($owner->refresh()->current_workspace_id)->toBe($workspace->id);
});

test('members cannot remove other members or themselves', function (bool $removeSelf) {
    [, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $target = $removeSelf ? $member : User::factory()->create();
    $workspace->users()->syncWithoutDetaching([
        $member->id => ['role' => MembershipRole::Member->value],
        $target->id => ['role' => MembershipRole::Member->value],
    ]);

    $this->actingAs($member)->delete(route('members.destroy', $target))->assertForbidden();

    $this->assertDatabaseHas('memberships', ['workspace_id' => $workspace->id, 'user_id' => $target->id]);
})->with(['another member' => false, 'themselves' => true]);

test('owners cannot remove members from another workspace', function () {
    [$owner] = ownerWithWorkspace();
    [, $otherWorkspace] = ownerWithWorkspace();
    $membership = Membership::factory()->create(['workspace_id' => $otherWorkspace->id, 'role' => MembershipRole::Member]);

    $this->actingAs($owner)->delete(route('members.destroy', $membership->user_id))->assertNotFound();

    $this->assertModelExists($membership);
});

test('guests cannot remove members', function () {
    $membership = Membership::factory()->create(['role' => MembershipRole::Member]);

    $this->delete(route('members.destroy', $membership->user_id))->assertRedirect(route('login'));

    $this->assertModelExists($membership);
});
