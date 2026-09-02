<?php

test('postgresql uses Financeiro defaults without database environment settings', function () {
    $environment = $_ENV;
    $server = $_SERVER;
    $processEnvironment = [];

    try {
        foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
            $processEnvironment[$key] = getenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        $database = require config_path('database.php');

        expect($database['default'])->toBe('pgsql');
        expect($database['connections']['pgsql'])
            ->toMatchArray([
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'financeiro',
                'username' => 'financeiro',
            ]);
    } finally {
        $_ENV = $environment;
        $_SERVER = $server;
        foreach ($processEnvironment as $key => $value) {
            putenv($value === false ? $key : $key.'='.$value);
        }
    }
});
