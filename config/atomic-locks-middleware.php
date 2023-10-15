<?php

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
