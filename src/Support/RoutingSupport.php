<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class RoutingSupport
{
    /**
     * Retorna um callable que carrega rotas e registra uma rota de health-check.
     *
     * @param string $routesRelativePath Caminho relativo ao arquivo de rotas a partir do base_path()
     */
    public static function makeRoutesLoader(string $routesRelativePath = 'routes/api.php'): callable
    {
        return function () use ($routesRelativePath): void {
            // carrega o arquivo de rotas fornecido
            require base_path($routesRelativePath);

            // registra um endpoint simples para verificar disponibilidade
            \Illuminate\Support\Facades\Route::get('/health', function () {
                $redis = app()->bound('redis.health') ? app('redis.health') : ['available' => null];

                return response()->json([
                    'ok'    => true,
                    'time'  => now()->toISOString(),
                    'redis' => $redis,
                ]);
            });
        };
    }
}
