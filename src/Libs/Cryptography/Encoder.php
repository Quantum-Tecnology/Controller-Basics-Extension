<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography;

use Illuminate\Http\JsonResponse;

use const JSON_THROW_ON_ERROR;

use JsonException;
use Vinkla\Hashids\Facades\Hashids;

class Encoder
{
    /**
     * Handle an incoming request.
     *
     * @throws JsonException
     */
    public static function run($response)
    {
        if ($response instanceof JsonResponse && '' !== $response->getContent()) {
            $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $responseData = self::encodeArray($responseData);

            $response->setContent(json_encode($responseData, JSON_THROW_ON_ERROR));
        }

        return $response;
    }

    public static function encodeArray($responseData): array
    {
        array_walk_recursive($responseData, function (&$value, $key): void {
            if (self::isIdentifier((string) $key) && self::isEncodable($value)) {
                $value = Hashids::encode($value);
            }
        });

        return $responseData;
    }

    /**
     * Hashids::encode requires a non-negative integer (or numeric string of
     * one). Null, empty strings, booleans and non-numeric strings would crash
     * gmp_mod inside Hashids — skip them and leave the original value.
     */
    private static function isEncodable(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 0;
        }

        if (is_string($value) && ctype_digit($value)) {
            return true;
        }

        return false;
    }

    /**
     * Check if parameter is an identifier.
     */
    private static function isIdentifier(string $paramKey, string $regexp = '/_id$|Id$/'): bool
    {
        return preg_match(config('hashids.regex'), $paramKey) || preg_match($regexp, $paramKey) || in_array($paramKey, config('hashids.attributes', []));
    }
}
