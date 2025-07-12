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
            if (self::isIdentifier((string) $key)) {
                $value = Hashids::encode($value);
            }
        });

        return $responseData;
    }

    /**
     * Check if parameter is an identifier.
     */
    private static function isIdentifier(string $paramKey, string $regexp = '/_id$|Id$/'): bool
    {
        return preg_match(config('hashids.regex'), $paramKey) || preg_match($regexp, $paramKey) || in_array($paramKey, config('hashids.attributes', []));
    }
}
