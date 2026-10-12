<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vinkla\Hashids\Facades\Hashids;

class Decoder
{
    /**
     * Handle an incoming request.
     */
    public static function run(Request $request): Request
    {
        self::decodeHeaderParams($request);
        self::decodeRouteParams($request);
        self::decodeRouteInputs($request);

        return $request;
    }

    protected static function abortIfInvalidIdentifier($key, $value, $sttribute): void
    {
        $value = collect(is_string($value) ? explode(',', $value) : $value);

        $value->each(function ($unit) use ($key, $sttribute): void {
            abort_if(
                !blank($unit) && !is_int($unit) && !self::isIgnoredUuid($unit) && self::isIdentifier($key) && !($newValue = current(Hashids::decode($unit))) && !is_int($newValue),
                Response::HTTP_BAD_REQUEST,
                __('Non-decodable values found in the request ' . $sttribute . '.')
            );
        });
    }

    private static function decodeHeaderParams(Request $request): void
    {
        foreach ($request->headers as $key => $value) {
            if (preg_match(config('hashids.headers.regex'), $key)) {
                $encodedIds = $value[0];
                $decodedIds = [];

                foreach (explode(',', (string) $encodedIds) as $unit) {
                    self::abortIfInvalidIdentifier($key, $unit, 'header-params');

                    $decoded = self::decodeUnit($unit);

                    if (self::wasDecoded($decoded)) {
                        $decodedIds[] = $decoded;
                    }
                }
                $decoded = implode(',', $decodedIds);

                if ('' !== $decoded && '0' !== $decoded) {
                    request()->headers->set($key, $decoded);
                }
            }
        }
    }

    private static function decodeRouteParams(Request $request): void
    {
        foreach (($request->route()?->parameters() ?? []) as $key => $value) {
            if (self::isIdentifier($key)) {
                // UUID ignorado fica como veio: nem hash, nem 404.
                if (self::isIgnoredUuid($value)) {
                    continue;
                }

                throw_if(!self::hashIsValid($value), NotFoundHttpException::class);
                self::abortIfInvalidIdentifier($key, $value, 'route-params');
                $decoded = current(Hashids::decode($value));

                if (self::wasDecoded($decoded)) {
                    $request->route()->setParameter($key, $decoded);
                } else {
                    abort(Response::HTTP_BAD_REQUEST, 'Identificador inválido.');
                }
            }
        }
    }

    private static function decodeRouteInputs(Request $request): void
    {
        $inputs = $request->all();
        self::decodeRecursive($inputs);
        $request->merge($inputs);
    }

    private static function decodeRecursive(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                if (self::isIdentifier((string) $key)) {
                    if (empty($value)) {
                        continue;
                    }

                    $decoded = collect($value)
                        ->transform(fn ($unit) => is_string($unit) ? self::decodeUnit($unit) : $unit)
                        ->filter(self::wasDecoded(...));

                    abort_if($decoded->isEmpty(), Response::HTTP_BAD_REQUEST, 'Identificador inválido.');

                    $value = $decoded->all();
                } else {
                    self::decodeRecursive($value);
                }

                continue;
            }

            if (!blank($value) && is_string($value) && self::isIdentifier((string) $key)) {
                self::abortIfInvalidIdentifier($key, $value, 'route-inputs');

                $decoded = collect(explode(',', $value))
                    ->transform(fn ($unit) => self::decodeUnit($unit))
                    ->filter(self::wasDecoded(...));

                abort_if($decoded->isEmpty(), Response::HTTP_BAD_REQUEST, 'Identificador inválido.');

                $value = 1 === $decoded->count() ? $decoded->first() : $decoded->implode(',');
            }
        }
    }

    /**
     * Decodifica uma unidade (hashid → inteiro). Com `hashids.ignore_uuid`, um
     * UUID volta intacto, o que permite misturar ids inteiros e UUID na mesma
     * lista (`?filter[ids]=v5rNVenmKP,0199a3f2-...`).
     */
    private static function decodeUnit(string $unit): mixed
    {
        return self::isIgnoredUuid($unit) ? $unit : current(Hashids::decode($unit));
    }

    /**
     * Valor que deve passar sem decodificar: UUID, com `hashids.ignore_uuid`.
     *
     * Por VALOR, não por nome: o `regex` só vê o nome do campo, e uma tabela de
     * chave UUID recebe o id em `{id}`/`*_id` como qualquer outra.
     */
    private static function isIgnoredUuid(mixed $value): bool
    {
        return is_string($value)
            && config('hashids.ignore_uuid', false)
            && Str::isUuid($value);
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
        $pattern  = '/^(?!undefined)[' . preg_quote((string) $alphabet, '/') . ']{1,' . $length . '}$/';

        return (bool) preg_match($pattern, $key);
    }
}
