<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vinkla\Hashids\Facades\Hashids;

class Decoder
{
    /**
     * Handle an incoming request.
     */
    public static function run(Request $request)
    {
        self::decodeHeaderParams($request);
        self::decodeRouteParams($request);
        self::decodeRouteInputs($request);

        return $request;
    }

    protected static function abortIfInvalidIdentifier($key, $value, $sttribute): void
    {
        $value = collect(is_string($value) ? explode(',', $value) : $value);

        $value->each(function ($unit) use ($key, $sttribute) {
            abort_if(
                !blank($unit) && !is_int($unit) && self::isIdentifier($key) && !($newValue = current(Hashids::decode($unit))) && !is_int($newValue),
                Response::HTTP_BAD_REQUEST,
                __('Non-decodable values found in the request ' . $sttribute . '.')
            );
        });
    }

    private static function decodeHeaderParams(Request $request): void
    {
        foreach ($request->headers as $key => $value) {
            if (preg_match(config('hashids.headers.regex'), (string) $key)) {
                $encodedIds = $value[0];
                $decodedIds = [];

                foreach (explode(',', $encodedIds) as $unit) {
                    self::abortIfInvalidIdentifier($key, $unit, 'header-params');

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
    }

    private static function decodeRouteParams(Request $request): void
    {
        foreach (($request->route()?->parameters() ?? []) as $key => $value) {
            if (self::isIdentifier($key)) {
                throw_if(!self::hashIsValid($value), NotFoundHttpException::class);
                self::abortIfInvalidIdentifier($key, $value, 'route-params');
                $decoded = current(Hashids::decode($value));

                if (self::wasDecoded($decoded)) {
                    $request->route()->setParameter($key, $decoded);
                } elseif (!config('app.debug')) {
                    abort(Response::HTTP_BAD_REQUEST, "Error decoding hashids by Inputs ['{$key}': '{$value}'].");
                }
            }
        }
    }

    private static function decodeRouteInputs(Request $request): void
    {
        $inputs = $request->all();

        array_walk($inputs, function (&$value, $key): void {
            if (!blank($value) && !is_int($value) && self::isIdentifier($key) && is_array($value)) {

                $value = collect($value)->transform(function ($unit) {
                    return current(Hashids::decode($unit));
                })->filter(function ($decoded) {
                    return self::wasDecoded($decoded);
                })->all();
            }
        });

        array_walk_recursive($inputs, function (&$value, $key): void {
            self::abortIfInvalidIdentifier($key, $value, 'route-inputs');

            if (!blank($value) && is_string($value) && self::isIdentifier($key)) {
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
    private static function isIdentifier(string | int $paramKey, string $regexp = '/_id$|Id$/'): bool
    {
        return preg_match(config('hashids.regex', ''), (string) $paramKey)
            || preg_match(config('hashids.headers.regex', ''), (string) $paramKey)
            || preg_match($regexp, (string) $paramKey)
            || in_array($paramKey, config('hashids.attributes', []));
    }

    private static function hashIsValid(string $key): bool
    {
        $alphabet = config('hashids.connections.' . config('hashids.default') . '.alphabet');
        $length   = config('hashids.connections.' . config('hashids.default') . '.length');
        $pattern  = '/^(?!undefined)[' . preg_quote($alphabet, '/') . ']{1,' . $length . '}$/';

        return (bool) preg_match($pattern, (string) $key);
    }
}
