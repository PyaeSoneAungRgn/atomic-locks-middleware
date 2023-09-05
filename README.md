# Atomic Locks Middleware

A package designed to ensure that only one request is processed at a time.

## Installation

```bash
composer require pyaesoneaung/atomic-locks-middleware
```

## Usage

By default, the atomic-locks-middleware uses `$request->user()?->id ?: $request->ip()` within atomic locks.

```php
Route::post('/order', function () {
    // ...
})->middleware('atomic-locks-middleware');
```

If you prefer to implement IP-based locking, you can use `atomic-locks-middleware:ip`.

```php
Route::post('/order', function () {
    // ...
})->middleware('atomic-locks-middleware:ip');
```

However, you have the flexibility to define `atomic-locks-middleware:{anything}` to customize the locking mechanism according to your preferences.

```php
Route::post('/order', function () {
    // ...
})->middleware('atomic-locks-middleware:{anything}');
```

## How Does It Work?

```php
// AtomicLocksMiddleware.php

public function handle(Request $request, Closure $next, string $option = null): Response
{
    $name = match ($option) {
        null => $request->user()?->id ?: $request->ip(),
        'ip' => $request->ip(),
        default => $option
    };

    $name = "{$request->path()}_{$name}";

    $lock = Cache::lock(
        config('atomic-locks-middleware.lock_prefix').$name,
        config('atomic-locks-middleware.lock_seconds')
    );
    app()->instance(config('atomic-locks-middleware.instance'), $lock);
    if ($lock->get()) {
        return $next($request);
    }

    return response()->json([
        'message' => config('atomic-locks-middleware.message'),
    ], 429);
}

public function terminate(Request $request, Response $response): void
{
    $instanceName = config('atomic-locks-middleware.instance');

    if (app()->bound($instanceName)) {
        app($instanceName)->release();
    }
}
```

The Atomic Locks Middleware uses [Laravel Atomic Locks](https://laravel.com/docs/10.x/cache#atomic-locks) in the background. It initiates a lock at the beginning of the middleware's execution and releases the lock once the response is dispatched to the browser.

## Publish Configuration

Publish the configuration for customization

```bash
php artisan vendor:publish --provider="PyaeSoneAung\AtomicLocksMiddleware\AtomicLocksMiddlewareServiceProvider"
```

```php
return [
    'middleware' => 'atomic-locks-middleware',
    'instance' => 'AtomicLocksMiddleware',
    'lock_prefix' => 'atomic_locks_middleware_',
    'lock_seconds' => 60,
    'message' => 'Too Many Attempts',
];
```

## Testing

```php
composer test
```
