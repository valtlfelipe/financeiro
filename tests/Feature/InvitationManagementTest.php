<?php

use App\MembershipRole;
use App\Models\Invitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('existing workspace members cannot be invited regardless of email casing', function (MembershipRole $role, string $email) {
    [$owner, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['email' => 'Member@Example.com']);
    $workspace->users()->attach($member, ['role' => $role->value]);

    $this->actingAs($owner)->from(route('invitations.index'))
        ->post(route('invitations.store'), ['email' => $email])
        ->assertRedirect(route('invitations.index'))
        ->assertSessionHasErrors(['email' => 'Este e-mail já pertence a uma pessoa deste espaço.']);

    $this->assertDatabaseCount('invitations', 0);
})->with([MembershipRole::Owner, MembershipRole::Member])
    ->with(['member@example.com', 'MEMBER@EXAMPLE.COM']);

test('the owner cannot invite their own email', function () {
    [$owner] = ownerWithWorkspace();

    $this->actingAs($owner)->post(route('invitations.store'), ['email' => $owner->email])
        ->assertSessionHasErrors(['email' => 'Este e-mail já pertence a uma pessoa deste espaço.']);

    $this->assertDatabaseCount('invitations', 0);
});

test('pending invitations cannot be duplicated regardless of email casing', function () {
    $this->freezeTime();
    [$owner, $workspace] = ownerWithWorkspace();
    $invitation = Invitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'Guest@Example.com',
    ]);

    $this->actingAs($owner)->post(route('invitations.store'), ['email' => 'GUEST@example.com'])
        ->assertSessionHasErrors(['email' => 'Já existe um convite pendente para este e-mail.']);

    $this->assertDatabaseCount('invitations', 1);
    $this->assertModelExists($invitation);
});

test('expired or accepted invitations do not prevent a new invitation', function (string $state) {
    $this->freezeTime();
    [$owner, $workspace] = ownerWithWorkspace();
    Invitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'guest@example.com',
        ...match ($state) {
            'expired' => ['expires_at' => now()],
            'accepted' => ['accepted_at' => now()],
        },
    ]);

    $this->actingAs($owner)->post(route('invitations.store'), ['email' => 'Guest@Example.com'])
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('invitations', 2);
    $this->assertDatabaseHas('invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'guest@example.com',
        'accepted_at' => null,
        'expires_at' => now()->addDays(7),
    ]);
})->with(['expired', 'accepted']);

test('an account and pending invitation in another workspace do not block invitations', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    [$otherOwner, $otherWorkspace] = ownerWithWorkspace();
    Invitation::factory()->create([
        'workspace_id' => $otherWorkspace->id,
        'invited_by' => $otherOwner->id,
        'email' => $otherOwner->email,
    ]);

    $this->actingAs($owner)->post(route('invitations.store'), ['email' => $otherOwner->email])
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('invitations', ['workspace_id' => $workspace->id, 'email' => $otherOwner->email]);
});

test('the owner can cancel a pending invitation and its link stops working', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $token = str_repeat('c', 64);
    $invitation = Invitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'cancelled@example.com',
        'token_hash' => hash('sha256', $token),
    ]);

    $this->actingAs($owner)
        ->withSession(['invitation_url' => route('invitations.show', $token)])
        ->delete(route('invitations.destroy', $invitation))
        ->assertRedirect(route('invitations.index'))
        ->assertSessionMissing('invitation_url');

    $this->assertModelMissing($invitation);
    $this->get(route('invitations.index'))->assertInertia(fn (Assert $page) => $page
        ->has('pendingInvitations', 0)->where('invitationUrl', null));
    auth()->logout();
    $this->get(route('invitations.show', $token))->assertNotFound();
    $this->post(route('invitations.accept', $token), [
        'name' => 'Convidado',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
    ])->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'cancelled@example.com']);

    $this->actingAs($owner)->post(route('invitations.store'), ['email' => 'cancelled@example.com'])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('invitations', ['workspace_id' => $workspace->id, 'email' => 'cancelled@example.com']);
});

test('members cannot cancel invitations', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);
    $invitation = Invitation::factory()->create(['workspace_id' => $workspace->id, 'invited_by' => $owner->id]);

    $this->actingAs($member)->delete(route('invitations.destroy', $invitation))->assertForbidden();

    $this->assertModelExists($invitation);
});

test('owners cannot cancel invitations from another workspace', function () {
    [$owner] = ownerWithWorkspace();
    $invitation = Invitation::factory()->create();

    $this->actingAs($owner)->delete(route('invitations.destroy', $invitation))->assertNotFound();

    $this->assertModelExists($invitation);
});

test('accepted invitations cannot be cancelled', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $invitation = Invitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'accepted_at' => now(),
    ]);

    $this->actingAs($owner)->delete(route('invitations.destroy', $invitation))->assertNotFound();

    $this->assertModelExists($invitation);
});

test('guests cannot cancel invitations', function () {
    $invitation = Invitation::factory()->create();

    $this->delete(route('invitations.destroy', $invitation))->assertRedirect(route('login'));

    $this->assertModelExists($invitation);
});
