<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Middleware;

use Closure;
use Illuminate\Http\Request;
use JsonException;
use QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography\Decoder;
use QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography\Encoder;
use QuantumTecnology\HandlerBasicsExtension\Traits\ApiResponseTrait;

class CryptographyMiddleware
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @throws JsonException
     */
    public function handle(Request $request, Closure $next, bool $asLocal = false)
    {
        // Run before application.
        $enabledForCryptography = $this->isEnabledForCryptography($request);

        if ($enabledForCryptography) {
            $request = Decoder::run($request);
            $request->merge(['beenEncrypted' => false]);
        }

        $response = $next($request);

        if ($enabledForCryptography) {
            $response = Encoder::run($response);
            $request->merge(['beenEncrypted' => true]);
        }

        return $response;
    }

    private function isEnabledForCryptography(Request $request): bool
    {
        if (!app()->isProduction() && $request->has('crypt')) {
            return $request->crypt === 'true';
        }

        return config('hashids.enable_cryptography', false)
            && ($request->beenEncrypted ?? true);
    }
}
