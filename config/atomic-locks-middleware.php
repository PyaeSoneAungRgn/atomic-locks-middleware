<?php

return [
    'middleware' => 'atomic-locks-middleware',
    'instance' => 'AtomicLocksMiddleware',
    'lock_prefix' => 'atomic_locks_middleware_',
    'lock_seconds' => 60,
    'message' => 'Too Many Attempts',
];
