<?php

test('the initial document honors the saved appearance before JavaScript loads', function (string $appearance, string $class) {
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->withUnencryptedCookie('appearance', $appearance)
        ->get(route('dashboard'))
        ->assertSee('data-appearance="'.$appearance.'" class="'.$class.'"', false);
})->with([
    'dark' => ['dark', 'dark'],
    'light' => ['light', ''],
    'system' => ['system', ''],
]);
