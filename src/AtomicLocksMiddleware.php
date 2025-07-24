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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        ?string $option = null,
        ?string $lockDuration = null,
        ?string $canBlock = null,
        ?string $blockDuration = null
    ): Response
    {
        if (! empty($canBlock)) {
            $canBlock = filter_var($canBlock, FILTER_VALIDATE_BOOLEAN);
        }

        $ip = $request->ip();
        $isAjax = $request->isJson() || $request->wantsJson();

        $name = match ($option) {
            null => $request->user()?->id ?: $ip,
            'ip' => $ip,
            default => $option
        };

        $cacheName = "{$request->path()}_{$name}";

        $lock = Cache::lock(
            $this->getConfigKey('lock_prefix').$cacheName,
            $lockDuration ?: $this->getConfigKey('default_lock_duration')
        );

        if (! $lock->get()) {
            if (! ($canBlock ?? $this->getConfigKey('can_block'))) {
                return $this->getResponse($this->getConfigKey('message'), 429, $isAjax);
            }

            try {
                $lock->block($blockDuration ?: $this->getConfigKey('default_block_duration'));
            } catch (LockTimeoutException) {
                return $this->getResponse($this->getConfigKey('block_timeout_error_message'), 500, $isAjax);
            } catch (Throwable $th) {
                return $this->getResponse($th->getMessage(), 500, $isAjax);
            } finally {
                $lock->release();
            }
        }

        app()->instance($this->getLockInstanceName(), $lock);

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        $instanceName = $this->getLockInstanceName();

        if (app()->bound($instanceName)) {
            app($instanceName)->release();
        }
    }

    private function getLockInstanceName(): string
    {
        return $this->getConfigKey('instance');
    }

    private function getConfigKey(string $key): mixed
    {
        return config('atomic-locks-middleware.'.$key);
    }

    private function getResponse(string $msg, int $code, bool $isAjax): mixed
    {
        $data = ['message' => $msg];

        return $isAjax
            ? response()->json($data, $code)
            : back($code)->with($data);
    }
}
