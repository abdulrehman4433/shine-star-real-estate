<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // Force the testing environment. `php artisan test` boots the app from .env first
        // (APP_ENV=local), and phpunit.xml <env> entries can't override Laravel's immutable env
        // repository — so without this the suite silently runs as 'local', which disables the
        // runningUnitTests() CSRF bypass and makes every POST-based test return 419.
        $app->detectEnvironment(fn () => 'testing');

        // CRITICAL SAFETY NET: the same env-repository quirk means phpunit.xml's
        // DB_CONNECTION=sqlite never actually reaches env(), so RefreshDatabase would run
        // migrate:fresh against the REAL mysql database on every test run and wipe it.
        // Force the config directly — tests are only ever allowed to touch in-memory sqlite.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }
}
