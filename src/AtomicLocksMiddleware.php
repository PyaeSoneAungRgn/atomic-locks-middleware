<?php

namespace PyaeSoneAung\AtomicLocksMiddleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AtomicLocksMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
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
            config('atomic-locks-middleware.lock_prefix').$name,
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
}
