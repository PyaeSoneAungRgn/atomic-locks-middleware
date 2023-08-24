<?php

namespace PyaeSoneAung\AtomicLocksMiddleware;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AtomicLocksMiddlewareServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('atomic-locks-middleware')
            ->hasConfigFile();
    }

    public function boot(): void
    {
        app('router')->aliasMiddleware(
            config('atomic-locks-middleware.middleware'),
            AtomicLocksMiddleware::class
        );
    }
}
