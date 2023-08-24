<?php

namespace PyaeSoneAung\AtomicLocksMiddleware\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use PyaeSoneAung\AtomicLocksMiddleware\AtomicLocksMiddlewareServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'PyaeSoneAung\\AtomicLocksMiddleware\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AtomicLocksMiddlewareServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_atomic-locks-middleware_table.php.stub';
        $migration->up();
        */
    }
}
