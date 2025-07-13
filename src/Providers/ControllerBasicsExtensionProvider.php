<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use QuantumTecnology\ControllerBasicsExtension\Middleware\LogMiddleware;

final class ControllerBasicsExtensionProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ([
            'hashids' => __DIR__ . '/../Config/hashids.php',
            'bind'    => __DIR__ . '/../Config/bind.php',
            'page'    => __DIR__ . '/../Config/page.php',
        ] as $key => $path) {
            $this->mergeConfigFrom($path, $key);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/hashids.php' => __DIR__ . '/../hashids.php',
            __DIR__ . '/../config/bind.php'    => __DIR__ . '/../bind.php',
            __DIR__ . '/../config/page.php'    => __DIR__ . '/../page.php',
        ], 'config');

        $this->app->make(Kernel::class)
            ->pushMiddleware(LogMiddleware::class);
    }
}
