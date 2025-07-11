<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Override;

final class ControllerBasicsExtensionProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $this->publishes([
            __DIR__ . '/../config/hashids.php' => config_path('hashids.php'),
        ], 'config');

        /*
         * TODO: esta apresentnado erro ao tentar registrar o provider
         */
        // $router->setRegistrar(new EnhancedResourceProvider($router));
    }
}
