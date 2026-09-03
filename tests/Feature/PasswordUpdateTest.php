<?php

use App\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('users can change only their own password without losing their session', function (MembershipRole $role) {
    [$user, $workspace] = ownerWithWorkspace();
    Membership::query()->where('user_id', $user->id)->update(['role' => $role]);
    $other = User::factory()->create();
    $otherPassword = $other->password;

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'New-financeiro-password-42!',
        'password_confirmation' => 'New-financeiro-password-42!',
        'id' => $other->id,
        'email' => 'unrequested@example.test',
        'current_workspace_id' => null,
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'))
        ->assertInertiaFlash('toast.message', 'Senha alterada com sucesso.');

    $this->assertAuthenticatedAs($user);
    expect(Hash::check('New-financeiro-password-42!', $user->fresh()->password))->toBeTrue();
    expect(Hash::check('password', $user->fresh()->password))->toBeFalse();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email, 'current_workspace_id' => $workspace->id]);
    $this->assertDatabaseHas('users', ['id' => $other->id, 'password' => $otherPassword]);
})->with([MembershipRole::Owner, MembershipRole::Member]);

test('guests cannot change a password', function () {
    [$user] = ownerWithWorkspace();
    $originalPassword = $user->password;

    $this->put(route('user-password.update'), [
        'id' => $user->id,
        'current_password' => 'password',
        'password' => 'New-financeiro-password-42!',
        'password_confirmation' => 'New-financeiro-password-42!',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseHas('users', ['id' => $user->id, 'password' => $originalPassword]);
});

test('the new password signs in after logout and the previous password is rejected', function () {
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'New-financeiro-password-42!',
        'password_confirmation' => 'New-financeiro-password-42!',
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->assertGuest();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'New-financeiro-password-42!',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('invalid password changes preserve the password and do not flash credentials', function (array $input, array $errors) {
    [$user] = ownerWithWorkspace();
    $originalPassword = $user->password;

    $this->actingAs($user)->from(route('profile.edit'))->put(route('user-password.update'), $input)
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors($errors)
        ->assertSessionMissing('_old_input.current_password')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $this->assertDatabaseHas('users', ['id' => $user->id, 'password' => $originalPassword]);
})->with([
    'missing fields' => [[], [
        'current_password' => 'O campo senha atual é obrigatório.',
        'password' => 'O campo senha é obrigatório.',
    ]],
    'wrong current password' => [[
        'current_password' => 'wrong-password',
        'password' => 'New-financeiro-password-42!',
        'password_confirmation' => 'New-financeiro-password-42!',
    ], ['current_password' => 'A senha atual está incorreta.']],
    'short password' => [[
        'current_password' => 'password',
        'password' => 'short',
        'password_confirmation' => 'short',
    ], ['password' => 'O campo senha deve ter pelo menos 8 caracteres.']],
    'mismatched confirmation' => [[
        'current_password' => 'password',
        'password' => 'New-financeiro-password-42!',
        'password_confirmation' => 'Different-password-42!',
    ], ['password' => 'A confirmação de senha não confere.']],
    'missing confirmation' => [[
        'current_password' => 'password',
        'password' => 'New-financeiro-password-42!',
    ], ['password' => 'A confirmação de senha não confere.']],
]);

test('password changes are limited to six attempts per minute', function () {
    [$user] = ownerWithWorkspace();
    $originalPassword = $user->password;
    $this->actingAs($user);

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->put(route('user-password.update'), [])->assertSessionHasErrors('current_password');
    }

    $this->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'New-financeiro-password-42!',
        'password_confirmation' => 'New-financeiro-password-42!',
    ])->assertTooManyRequests();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'password' => $originalPassword]);
});
