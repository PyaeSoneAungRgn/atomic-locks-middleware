<?php

use PyaeSoneAung\AtomicLocksMiddleware\AtomicLocksMiddleware;

it('can create lock and terminate', function (): void {
    (new AtomicLocksMiddleware)->handle(request(), fn () => response()->json());
    (new AtomicLocksMiddleware)->terminate(request(), response()->json());
    expect(app(config('atomic-locks-middleware.instance'))?->release())->toBeFalse();
});

it('can block the next request if the current one is still processing', function (): void {

    $duration = 5;

    config()->set('atomic-locks-middleware.can_block', true);
    config()->set('atomic-locks-middleware.default_lock_duration', $duration);
    config()->set('atomic-locks-middleware.default_block_duration', $duration);

    $startTime = now();
    (new AtomicLocksMiddleware)->handle(request(), fn () => response()->json());

    (new AtomicLocksMiddleware)->handle(request(), fn () => response()->json());

    $elapsedTime = now()->diffInRealSeconds($startTime);

    expect($elapsedTime)->toBe($duration);
});

it('can return an error message if the lock is not acquired in time', function (): void {
    $duration = 5;

    config()->set('atomic-locks-middleware.can_block', true);
    config()->set('atomic-locks-middleware.default_lock_duration', $duration);
    config()->set('atomic-locks-middleware.default_block_duration', $duration - 3);

    (new AtomicLocksMiddleware)->handle(request(), fn () => response()->json());

    $response = (new AtomicLocksMiddleware)->handle(request(), fn () => response()->json());

    expect($response->getStatusCode())->toBe(500);

    expect($response->getContent())->toContain(config('atomic-locks-middleware.block_timeout_error_message'));
});
