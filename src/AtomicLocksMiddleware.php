<?php

namespace PyaeSoneAung\AtomicLocksMiddleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AtomicLocksMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $lock = Cache::lock(
            config('atomic-locks-middleware.lock_prefix') . $request->user()?->id ?: $request->ip(),
            config('atomic-locks-middleware.lock_seconds')
        );
        app()->instance(config('atomic-locks-middleware.instacnce'), $lock);
        if ($lock->get()) {
            return $next($request);
        }
        return response()->json(['message' => 'Too Many Attempts'], 429);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(config('atomic-locks-middleware.instacnce'))?->release();
    }
}
