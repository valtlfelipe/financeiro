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
