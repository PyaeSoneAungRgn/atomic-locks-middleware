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
        $this->publishes([
            __DIR__.'/../config/atomic-locks-middleware.php' => config_path('atomic-locks-middleware.php'),
        ]);

        app('router')->aliasMiddleware(
            config('atomic-locks-middleware.middleware'),
            AtomicLocksMiddleware::class
        );
    }
}
