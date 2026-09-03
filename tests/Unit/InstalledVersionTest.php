<?php

use App\InstalledVersion;

test('the image release file takes precedence over inherited environment values', function () {
    $releaseFile = tempnam(sys_get_temp_dir(), 'financeiro-version-');
    file_put_contents($releaseFile, "v1.3.0\n");

    try {
        expect(InstalledVersion::detect($releaseFile, 'v1.2.0'))->toBe('v1.3.0');
    } finally {
        unlink($releaseFile);
    }
});

test('an environment version is used outside a release image', function () {
    expect(InstalledVersion::detect('/missing/financeiro-version', 'v1.4.0'))->toBe('v1.4.0')
        ->and(InstalledVersion::detect('/missing/financeiro-version', null))->toBe('dev');
});

test('an invalid image release file falls back safely', function () {
    $releaseFile = tempnam(sys_get_temp_dir(), 'financeiro-version-');
    file_put_contents($releaseFile, 'not-a-release');

    try {
        expect(InstalledVersion::detect($releaseFile, 'v1.4.0'))->toBe('v1.4.0');
    } finally {
        unlink($releaseFile);
    }
});
