<?php

use App\Models\User;

test('the user locale persists and unsupported locales are rejected', function () {
    [$user] = ownerWithWorkspace();
    $this->actingAs($user)->patch(route('locale.update'), ['locale' => 'pt-BR'])->assertRedirect();
    expect($user->refresh()->locale)->toBe('pt-BR');
    $this->actingAs($user)->patch(route('locale.update'), ['locale' => 'en'])->assertSessionHasErrors('locale');
});

test('unsupported stored locales fall back to portuguese', function () {
    [$user] = ownerWithWorkspace();
    User::query()->whereKey($user->id)->update(['locale' => 'xx']);
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    expect(app()->getLocale())->toBe('pt_BR');
});

test('frontend locale catalogs contain the complete base namespaces', function () {
    foreach (['common', 'auth', 'finance', 'settings'] as $namespace) {
        $path = resource_path("js/i18n/locales/pt-BR/{$namespace}.json");
        expect(file_exists($path))->toBeTrue()
            ->and(json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR))->toBeArray();
    }
});
