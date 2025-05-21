<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckRouteLevelMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $level)
    {
        abort_unless(
            auth()->check(),
            Response::HTTP_UNAUTHORIZED,
            __('Unauthorized')
        );

        $canPass = match (auth()->level()) {
            'beta'  => ['beta'],
            'alpha' => ['alpha', 'beta'],
            'test'  => ['test', 'alpha', 'beta'],
            default => [],
        };

        abort_unless(
            in_array($level, $canPass),
            Response::HTTP_FORBIDDEN,
            __('Access denied')
        );

        return $next($request);
    }
}
