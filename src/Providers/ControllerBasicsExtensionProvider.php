<?php

namespace GustavoSantarosa\ControllerBasicsExtension\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

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
        $router->setRegistrar(new EnhancedResourceProvider($router));
    }
}
