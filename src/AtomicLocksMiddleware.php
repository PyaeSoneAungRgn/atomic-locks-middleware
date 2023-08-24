<?php

namespace PyaeSoneAung\AtomicLocksMiddleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AtomicLocksMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $option = null): Response
    {
        $name = match ($option) {
            null => $request->user()?->id ?: $request->ip(),
            'ip' => $request->ip(),
            default => $option
        };

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
}
