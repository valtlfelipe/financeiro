<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the page and mail sender keep the product name despite legacy environment overrides', function () {
    $environment = $_ENV;
    $server = $_SERVER;
    $this->withoutVite();

    try {
        $_ENV['APP_NAME'] = $_SERVER['APP_NAME'] = 'Custom product';
        $_ENV['MAIL_FROM_NAME'] = $_SERVER['MAIL_FROM_NAME'] = 'Custom sender';
        config([
            'app.name' => (require config_path('app.php'))['name'],
            'mail.from.name' => (require config_path('mail.php'))['from']['name'],
            'inertia.ssr.enabled' => false,
        ]);

        $response = $this->get('/setup');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Setup')
            ->where('name', 'Financeiro'));
        $response->assertSee('<title>Financeiro</title>', escape: false);
        expect(config('mail.from.name'))->toBe('Financeiro');
    } finally {
        $_ENV = $environment;
        $_SERVER = $server;
    }
});

test('infrastructure defaults use the product name independently of APP_NAME', function () {
    $environment = $_ENV;
    $server = $_SERVER;

    try {
        $_ENV['APP_NAME'] = $_SERVER['APP_NAME'] = 'Custom product';

        expect((require config_path('cache.php'))['prefix'])->toBe('financeiro-cache-');
        expect((require config_path('session.php'))['cookie'])->toBe('financeiro-session');
        expect((require config_path('database.php'))['redis']['options']['prefix'])->toBe('financeiro-database-');
        expect((require config_path('logging.php'))['channels']['slack']['username'])->toBe('Financeiro');
    } finally {
        $_ENV = $environment;
        $_SERVER = $server;
    }
});

test('deployment-specific infrastructure names remain configurable', function () {
    $environment = $_ENV;
    $server = $_SERVER;

    try {
        foreach ([
            'CACHE_PREFIX' => 'instance-cache-',
            'SESSION_COOKIE' => 'instance-session',
            'REDIS_PREFIX' => 'instance-database-',
            'LOG_SLACK_USERNAME' => 'Instance alerts',
        ] as $key => $value) {
            $_ENV[$key] = $_SERVER[$key] = $value;
        }

        expect((require config_path('cache.php'))['prefix'])->toBe('instance-cache-');
        expect((require config_path('session.php'))['cookie'])->toBe('instance-session');
        expect((require config_path('database.php'))['redis']['options']['prefix'])->toBe('instance-database-');
        expect((require config_path('logging.php'))['channels']['slack']['username'])->toBe('Instance alerts');
    } finally {
        $_ENV = $environment;
        $_SERVER = $server;
    }
});
