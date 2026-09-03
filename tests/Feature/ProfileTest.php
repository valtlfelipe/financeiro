<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('members can view their own profile', function () {
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->has('passwordRules')
            ->where('auth.user.name', $user->name)
            ->where('auth.user.email', $user->email));
});

test('guests cannot read or update a profile', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('profile.update'), [])->assertRedirect(route('login'));
});

test('profile updates only the authenticated users name and email', function () {
    [$user] = ownerWithWorkspace();
    $other = User::factory()->create();
    $originalPassword = $user->password;

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Novo nome',
        'email' => 'novo@example.com',
        'id' => $other->id,
        'password' => 'unrequested-password',
        'current_workspace_id' => null,
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'))
        ->assertInertiaFlash('toast.message', 'Perfil atualizado.');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Novo nome',
        'email' => 'novo@example.com',
        'password' => $originalPassword,
        'current_workspace_id' => $user->current_workspace_id,
        'email_verified_at' => null,
    ]);
    $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => $other->name, 'email' => $other->email]);
});

test('profile rejects invalid input without changing the user', function (array $input, array $errors) {
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->patch(route('profile.update'), $input)
        ->assertSessionHasErrors($errors);

    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
})->with([
    'required' => [[], ['name', 'email']],
    'invalid email' => [['name' => 'Nome', 'email' => 'invalid'], ['email']],
    'long name' => [['name' => str_repeat('a', 256), 'email' => 'valid@example.com'], ['name']],
]);

test('profile rejects an email belonging to another user', function () {
    [$user] = ownerWithWorkspace();
    $other = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => $other->email])
        ->assertSessionHasErrors('email');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
});

test('saving an unchanged email preserves its verification', function () {
    [$user] = ownerWithWorkspace();
    $verifiedAt = $user->email_verified_at;

    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Outro nome', 'email' => $user->email])
        ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at->equalTo($verifiedAt))->toBeTrue();
});
