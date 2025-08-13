<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

final class LogMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($enableQueryLog = $this->shouldEnableQueryLog($request)) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        match (true) {
            $response instanceof JsonResponse => (function () use (&$response, $enableQueryLog): void {
                $data = $response->getData(true);

                if (($messages = LogSupport::getMessages()) !== []) {
                    $data['quantum_log'] = $messages;
                }

                if ($enableQueryLog) {
                    $data['query_log'] = array_map(function (array $entry) {
                        $entry['query'] = str_replace(['\\', '"'], ['', ''], $entry['query']);

                        return $entry;
                    }, DB::getQueryLog());
                }

                $response->setData($data);
            })(),
            default => when(LogSupport::getMessages(), fn () => Log::debug(json_encode(LogSupport::getMessages()))),
        };

        return $response;
    }

    private function shouldEnableQueryLog(Request $request): bool
    {
        return !app()->isProduction() && app()->hasDebugModeEnabled() && $request->has('enable_query_log');
    }
}
