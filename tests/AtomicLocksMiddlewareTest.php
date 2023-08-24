<?php

use PyaeSoneAung\AtomicLocksMiddleware\AtomicLocksMiddleware;

it('can create lock and terminate', function () {
    (new AtomicLocksMiddleware)->handle(request(), fn () => response()->json());
    (new AtomicLocksMiddleware)->terminate(request(), response()->json());
    expect(app(config('atomic-locks-middleware.instance'))?->release())->toBeFalse();
});
