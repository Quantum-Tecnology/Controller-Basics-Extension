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
        /*
         * TODO: esta apresentnado erro ao tentar registrar o provider
         */
        // $router->setRegistrar(new EnhancedResourceProvider($router));
    }
}
