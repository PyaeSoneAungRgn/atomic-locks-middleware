<?php

namespace PyaeSoneAung\AtomicLocksMiddleware\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PyaeSoneAung\AtomicLocksMiddleware\AtomicLocksMiddlewareServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            AtomicLocksMiddlewareServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
    }
}
