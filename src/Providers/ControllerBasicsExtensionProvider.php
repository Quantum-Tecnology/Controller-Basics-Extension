<?php

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ControllerBasicsExtensionProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->publishes([
            __DIR__.'/../config/hashids.php' => config_path('hashids.php'),
        ], 'config');

        /*
         * TODO: esta apresentnado erro ao tentar registrar o provider
         */
        // $router->setRegistrar(new EnhancedResourceProvider($router));
    }
}
