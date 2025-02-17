<?php

namespace GustavoSantarosa\ControllerBasicsExtension\Providers;

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
        $router->register(new EnhancedResourceProvider($router));
    }
}
