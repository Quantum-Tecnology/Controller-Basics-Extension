<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use QuantumTecnology\ControllerBasicsExtension\Middleware\LogMiddleware;

final class ControllerGraphQL extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', // caminho do config padrão do pacote
            'quantum-controller-graphql' // nome da chave de configuração
        );
    }

    public function boot(): void
    {
        $this->app->make(Kernel::class)
            ->pushMiddleware(LogMiddleware::class);

    }
}
