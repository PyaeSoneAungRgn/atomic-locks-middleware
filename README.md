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

You can also pass additional parameters to the middleware for more customization. The available parameters are:
- `{anything} (string)` : Your custom locking mechanism.
- `{lockDuration} (int)` : Duration for which the lock will be held.
- `{canBlock} (bool)` : Whether the request can wait for the lock or not.
- `{blockDuration} (int)` : If waiting is allowed, the maximum duration to wait for the lock.

> If no additional parameters are provided, the default values from the config file will be used.

```php
Route::post('/order', function () {
    // ...
})->middleware('atomic-locks-middleware:{anything}');


Route::post('/purchase', function () {
    // ...
})->middleware('atomic-locks-middleware:{anything},60,true,60');


Route::post('/payment/process', function () {
    // ...
})->middleware('atomic-locks-middleware:{anything},60,false');
```

## How Does It Work?

```php
// AtomicLocksMiddleware.php

 public function handle(Request $request, Closure $next, string $option = null, int $lockDuration = null, string $canBlock = null, int $blockDuration = null): Response
{
    if (! empty($canBlock)) {
        $canBlock = filter_var($canBlock, FILTER_VALIDATE_BOOLEAN);
    }

    $name = match ($option) {
        null => $request->user()?->id ?: $request->ip(),
        'ip' => $request->ip(),
        default => $option
    };

    $name = "{$request->path()}_{$name}";

    $lock = Cache::lock(
        config('atomic-locks-middleware.lock_prefix') . $name,
        $lockDuration ?: config('atomic-locks-middleware.default_lock_duration')
    );

    if (! $lock->get()) {
        if (! ($canBlock ?? config('atomic-locks-middleware.can_block'))) {
            return response()->json([
                'message' => config('atomic-locks-middleware.message'),
            ], 429);
        }

        try {
            $lock->block($blockDuration ?: config('atomic-locks-middleware.default_block_duration'));
        } catch (LockTimeoutException) {
            $lock->release();

            return response()->json([
                'message' => config('atomic-locks-middleware.block_timeout_error_message'),
            ], 500);
        } catch (Throwable $th) {
            $lock->release();

            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    app()->instance(config('atomic-locks-middleware.instance'), $lock);

    return $next($request);
}

/**
 * Handle tasks after the response has been sent to the browser.
 */
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

    'middleware_name' => 'atomic-locks-middleware',
    'middleware_class' => PyaeSoneAung\AtomicLocksMiddleware\AtomicLocksMiddleware::class,

    'instance' => 'AtomicLocksMiddleware',

    'lock_prefix' => 'atomic_locks_middleware_',
    'default_lock_duration' => 60,

    'can_block' => true,
    'default_block_duration' => 60, // It's generally recommended to set the block duration to be longer than the lock duration.
    'block_timeout_error_message' => 'Timeout: Unable to acquire lock within the specified time.',

    'message' => 'Too Many Attempts',
];

```

## Testing

```php
composer test
```
