<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

use Illuminate\Database\Eloquent\Model;

final class FilterParser
{
    public static function extract(Model $model, array $data, string $prefixKey = 'filter'): array
    {
        $filters = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (!str_starts_with($key, $prefixKey)) {
                continue;
            }

            $openParenPos  = mb_strpos($key, '(');
            $closeParenPos = mb_strrpos($key, ')');

            if (false === $openParenPos) {
                continue;
            }

            if (false === $closeParenPos) {
                continue;
            }

            if ($closeParenPos < $openParenPos) {
                continue;
            }

            $prefix = mb_substr($key, 0, $openParenPos);
            $inside = mb_substr($key, $openParenPos + 1, $closeParenPos - $openParenPos - 1);

            if ($prefix === $prefixKey) {
                $group = $model::class;
            } else {
                $expected = $prefixKey . '_';
                $group    = str_starts_with($prefix, $expected) ? mb_substr($prefix, mb_strlen($expected)) : '';

                if ('' === $group) {
                    $group = $model::class;
                }
            }

            $field     = $inside;
            $operation = '=';

            if (str_contains($inside, ',')) {
                [$field, $op] = array_pad(explode(',', $inside, 2), 2, null);
                $field        = mb_trim((string) $field);
                $operation    = mb_trim((string) ($op ?? '='));

                if ('' === $operation) {
                    $operation = '=';
                }
            } else {
                $field = mb_trim($field);

                if (str_starts_with($field, 'by')) {
                    $operation = 'by';
                }
            }

            if ('' === $field) {
                continue;
            }

            if (is_null($value) && !in_array(mb_strtolower($operation), ['null', 'not-null'], true)) {
                continue;
            }

            $filters[$group] ??= [];
            $filters[$group][$field] ??= [];

            if (in_array(mb_strtolower($operation), ['null', 'not-null'], true)) {
                $filters[$group][$field][] = [
                    'operation' => $operation,
                    'value'     => 'true' === $value,
                ];

                continue;
            }

            $normalized = is_string($value) && str_contains($value, '|') ? explode('|', $value) : [$value];

            $filters[$group][$field][] = [
                'operation' => $operation,
                'value'     => collect($normalized),
            ];
        }

        return $filters;
    }
}
