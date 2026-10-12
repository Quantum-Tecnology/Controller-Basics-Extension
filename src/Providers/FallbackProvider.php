<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use ReflectionProperty;
use Throwable;

class FallbackProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if (!(bool) config('fallback.on_boot', true)) {
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
            Redis::connection('default')->ping();
            $redisAvailable = true;
        } catch (Throwable) {
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
                Cache::setDefaultDriver('database');

                app()->forgetInstance('cache.store');

                // O RateLimiter é um SINGLETON que guarda o store resolvido no
                // momento em que foi criado — e ele costuma ser criado ANTES
                // deste boot(). Sem trocar o store dele, ele continua segurando
                // o RedisStore antigo e toda rota com `throttle` (o LOGIN,
                // entre elas) segue batendo no Redis morto e devolvendo 500,
                // mesmo com o cache já caído para database.
                //
                // Troca-se o store POR DENTRO, em vez de esquecer a instância:
                // os limiters ficam registrados NO PRÓPRIO objeto (RateLimiter
                // ::for('login', ...) no AppServiceProvider). Recriá-lo perde
                // todos eles e o 500 de Redis vira
                // "Rate limiter [login] is not defined".
                if (app()->resolved(RateLimiter::class)) {
                    try {
                        $limiter  = app(RateLimiter::class);
                        $property = new ReflectionProperty($limiter, 'cache');
                        $property->setAccessible(true);
                        $property->setValue($limiter, Cache::driver('database'));
                    } catch (Throwable) {
                        // Se a propriedade mudar de nome numa versão futura do
                        // framework, seguir sem trocar é melhor que derrubar o
                        // boot inteiro por causa de um fallback.
                    }
                }
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
