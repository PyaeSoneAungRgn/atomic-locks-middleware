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
        parent::boot();

        app('router')->aliasMiddleware(
            config('atomic-locks-middleware.middleware_name'),
            config('atomic-locks-middleware.middleware_class'),
        );
    }
}
