<?php

namespace QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vinkla\Hashids\Facades\Hashids;

class Decoder
{
    /**
     * Handle an incoming request.
     *
     */
    public static function run(Request $request)
    {
        // Decode Headers - TODO: Disable in future.
        foreach ($request->headers as $key => $value) {
            if (preg_match('/^(X-Admin|X-Agent|X-User)/i', $key)) {
                $encodedIds = $value[0];
                $decodedIds = [];
                foreach (explode(',', $encodedIds) as $unit) {
                    $decoded = current(Hashids::decode($unit));
                    if (self::wasDecoded($decoded)) {
                        $decodedIds[] = $decoded;
                    }
                }
                $decoded = implode(',', $decodedIds);
                if ($decoded) {
                    request()->headers->set($key, $decoded);
                }
            }
        }

        // Decode Route Params
        foreach (($request->route()?->parameters() ?? []) as $key => $value) {
            if (self::isIdentifierFromPath($key)) {
                throw_if(!self::hashIsValid($value), NotFoundHttpException::class);
                $decoded = current(Hashids::decode($value));

                if (self::wasDecoded($decoded)) {
                    $request->route()->setParameter($key, $decoded);
                } elseif (!config('app.debug')) {
                    abort(Response::HTTP_BAD_REQUEST, "Error decoding hashids by Inputs ['{$key}': '{$value}'].");
                }
            }
        }

        // Decode Input Params
        $inputs = $request->all();

        array_walk($inputs, function (&$value, $key): void {
            if ($value && self::isIdentifierFromBody($key) && is_array($value)) {
                $value = collect($value)->transform(function ($unit) {
                    return current(Hashids::decode($unit));
                })->filter(function ($decoded) {
                    return self::wasDecoded($decoded);
                })->all();
            }
        });

        array_walk_recursive($inputs, function (&$value, $key): void {
            if ($value && self::isIdentifierFromBody($key)) {
                $value = collect(explode(',', $value))->transform(function ($unit) {
                    return current(Hashids::decode($unit));
                })->filter(function ($decoded) {
                    return self::wasDecoded($decoded);
                })->implode(',');

                $decoded = current(Hashids::decode($value));

                if (self::wasDecoded($decoded)) {
                    $value = $decoded;
                } elseif (!config('app.debug')) {
                    abort(Response::HTTP_BAD_REQUEST, "Error decoding hashids by Inputs ['{$key}': '{$value}'].");
                }
            }
        });

        $request->merge($inputs);

        return $request;
    }

    /**
     * Check if the decoded hashid has a valid value.
     */
    private static function wasDecoded($decodedHash): bool
    {
        return $decodedHash || 0 === $decodedHash;
    }

    /**
     * Check if parameter is an identifier.
     */
    private static function isIdentifier(string $paramKey, string $regexp = '/_id$|Id$/'): bool
    {
        return 'id' === $paramKey || preg_match($regexp, $paramKey);
    }

    private static function isIdentifierFromPath(string $paramKey): bool
    {
        return self::isIdentifier($paramKey, '/Id$/');
    }

    private static function isIdentifierFromBody(string $paramKey): bool
    {
        return self::isIdentifier($paramKey, '/_id$/');
    }

    private static function hashIsValid(string $key): bool
    {
        $alphabet = config('hashids.connections.'.config('hashids.default').'.alphabet');
        $length   = config('hashids.connections.'.config('hashids.default').'.length');
        $pattern  = '/^(?!undefined)['.preg_quote($alphabet, '/').']{1,'.$length.'}$/';

        return (bool) preg_match($pattern, $key);
    }
}
