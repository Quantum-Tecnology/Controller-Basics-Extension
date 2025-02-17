<?php

namespace GustavoSantarosa\ControllerBasicsExtension\Providers;

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
    public function boot()
    {
        $this->app->register(EnhancedResourceProvider::class);
    }
}
