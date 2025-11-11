<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Support\ServiceProvider;
use Throwable;

class FallbackProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (!(bool) config('fallback.on_boot', true)) {
            return;
        }
    }

    public function boot(): void
    {
        if (!(bool) config('fallback.on_boot', true)) {
            return;
        }

        $this->healthCheck();
    }

    protected function healthCheck(): void
    {
        // Allow disabling via env if needed
        if (!(bool) config('fallback.auto_fallback_redis', false)) {
            return;
        }

        $redisAvailable = null;

        try {
            // Try to ping Redis quickly; if it throws, we'll fallback drivers below
            // Using default connection; adjust if you use custom names
            \Illuminate\Support\Facades\Redis::connection('default')->ping();
            $redisAvailable = true;
        } catch (Throwable $e) {
            $redisAvailable = false;
        }

        // Share status for optional diagnostics (e.g., health route)
        app()->instance('redis.health', [
            'available' => $redisAvailable,
        ]);

        if (false === $redisAvailable) {
            // Fallback cache store if currently set to redis
            if ('redis' === config('cache.default')) {
                config(['cache.default' => 'database']);
                // Ensure the cache manager uses the new default in this request lifecycle
                app()->forgetInstance('cache');
                \Illuminate\Support\Facades\Cache::setDefaultDriver('database');
            }

            // Fallback session driver if currently set to redis
            if ('redis' === config('session.driver')) {
                config(['session.driver' => 'database']);
                // No need to rebind here as StartSession middleware reads config later in the pipeline
            }

            // Fallback queue default if currently set to redis (note: queue workers should be supervised)
            if ('redis' === config('queue.default')) {
                config(['queue.default' => 'database']);
            }

            // Optional: if broadcasting is using redis, fallback to log
            if ('redis' === config('broadcasting.default')) {
                config(['broadcasting.default' => 'log']);
            }

            // Log once per request
            logger()->warning('Redis indisponível. Aplicando fallback para drivers baseados em database/log.', [
                'cache_default'  => config('cache.default'),
                'session_driver' => config('session.driver'),
                'queue_default'  => config('queue.default'),
                'broadcasting'   => config('broadcasting.default'),
            ]);
        }
    }
}
