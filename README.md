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

The Atomic Locks Middleware uses [Laravel Atomic Locks](https://laravel.com/docs/10.x/cache#atomic-locks) in the background. It initiates a lock at the beginning of the middleware's execution and releases the lock once the response is dispatched to the browser.

### PS:

we cant use this middleware on routes that runs a queue inside the request, because
1. the lock is released on the request termination
2. the queue needs the lock name so it can release it when done

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

    'can_block' => false,
    'default_block_duration' => 60, // It's generally recommended to set the block duration to be longer than the lock duration.
    'block_timeout_error_message' => 'Timeout: Unable to acquire lock within the specified time.',

    'message' => 'Too Many Attempts',
];

```

## Testing

```php
composer test
```
