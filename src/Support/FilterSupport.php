<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class FilterSupport
{
    public function parse(array $data): array
    {
        $filters = [];

        foreach ($data as $key => $value) {
            if (preg_match('/^filter_?(\w*)?\(([^,()]+)(?:,([^\)]+))?\)$/', $key, $matches)) {
                [$relationPath, $field, $operator] = [
                    '' !== $matches[1] ? $matches[1] : '[__model__]',
                    $matches[2],
                    $matches[3] ?? '=',
                ];

                if (is_string($value)) {
                    $parsedValues = preg_split('/[,\|]/', $value);
                } elseif (is_array($value)) {
                    $parsedValues = $value;
                } else {
                    $parsedValues = [$value];
                }

                $parsedValues = array_map(static function ($v): int | string {
                    $v = mb_trim((string) $v);

                    return is_numeric($v) ? (int) $v : $v;
                }, $parsedValues);

                $filters[$relationPath][$field][$operator] = $parsedValues;
            }
        }

        return $this->cleanFilters($filters);
    }

    protected function cleanFilters(array $filters): array
    {
        foreach ($filters as $relation => &$fields) {
            foreach ($fields as $field => &$operators) {
                foreach ($operators as $operator => &$values) {
                    $values = array_filter($values, fn ($v): bool => !(null === $v || '' === $v || [] === $v));

                    if ([] === $values) {
                        unset($operators[$operator]);
                    } else {
                        $operators[$operator] = array_values($values); // reindex
                    }
                }
                unset($values);

                if (empty($operators)) {
                    unset($fields[$field]);
                }
            }
            unset($operators);

            if (empty($fields)) {
                unset($filters[$relation]);
            }
        }
        unset($fields);

        return $filters;
    }
}
