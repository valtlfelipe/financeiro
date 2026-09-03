<?php

test('the self-host compose stack does not require a dotenv file', function () {
    $compose = file_get_contents(dirname(__DIR__, 2).'/compose.yaml');

    expect($compose)
        ->not->toContain('env_file:')
        ->and($compose)->not->toContain('build:')
        ->and($compose)->toContain('APP_KEY: ${APP_KEY')
        ->and($compose)->toContain('DB_PASSWORD: ${DB_PASSWORD')
        ->and($compose)->toContain('ghcr.io/valtlfelipe/financeiro');
});

test('release images persist their version independently of container environment inheritance', function () {
    $dockerfile = file_get_contents(dirname(__DIR__, 2).'/Dockerfile');

    expect($dockerfile)
        ->toContain('ARG FINANCEIRO_VERSION=dev')
        ->toContain('> /app/VERSION');
});

test('container startup clears deployment caches before rebuilding them', function () {
    $entrypoint = file_get_contents(dirname(__DIR__, 2).'/docker/entrypoint.sh');

    expect($entrypoint)
        ->toContain('php artisan config:clear')
        ->toContain('php artisan route:clear')
        ->toContain('php artisan view:clear')
        ->toContain('php artisan config:cache')
        ->toContain('php artisan route:cache')
        ->toContain('php artisan view:cache');
});
