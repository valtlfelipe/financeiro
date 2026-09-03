<?php

use App\Models\User;

test('public registration routes are disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

test('the first visit creates the only public owner account', function () {
    $this->get('/')->assertRedirect(route('setup.create'));

    $this->post(route('setup.store'), [
        'name' => 'Felipe',
        'workspace_name' => 'Casa',
        'icon' => 'house',
        'email' => 'felipe@example.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->sole();
    expect($user->currentWorkspace?->name)->toBe('Casa')
        ->and($user->locale)->toBe('pt-BR')
        ->and($user->workspaces()->first()?->pivot->role)->toBe('owner')
        ->and($user->currentWorkspace?->accounts)->toHaveCount(1)
        ->and($user->currentWorkspace?->categories)->toHaveCount(2);

    auth()->logout();
    $this->get(route('setup.create'))->assertRedirect(route('login'));
});
