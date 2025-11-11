<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

class ApplyFilter
{
    public static function execute($query, array $filters = [])
    {
        $table = $query->getModel()->getTable();

        foreach ($filters as $field => $items) {
            $query = $query->where(function ($query) use ($table, $field, $items): void {
                $qualifiedField = $table . '.' . $field;

                foreach ($items as $item) {
                    $op     = $item['operation'] ?? '=';
                    $values = $item['value'] ?? null;

                    // Handle null/not-null operations
                    $lowerOp = is_string($op) ? mb_strtolower($op) : $op;

                    if ('null' === $lowerOp) {

                        $values ? $query->whereNull($qualifiedField) : $query->whereNotNull($qualifiedField);

                        continue;
                    }

                    if ('not-null' === $lowerOp) {
                        $values ? $query->whereNotNull($qualifiedField) : $query->whereNull($qualifiedField);

                        continue;
                    }

                    if (in_array($newValue = $values->first(), ['true', 'false'], true)) {
                        $newValue = 'true' === $newValue;
                        $query->where($qualifiedField, $op, $newValue);

                        continue;
                    }

                    if (in_array($op, ['=', '=='], true)) {
                        $query->whereIn($qualifiedField, $values);

                        continue;
                    }

                    if (str_starts_with((string) $field, 'by_')) {
                        $newFilter = str($field)->camel()->toString();

                        $query->{$newFilter}(collect(explode('|', $op)), $values);

                        continue;
                    }

                    foreach ($values as $v) {
                        $query->where($qualifiedField, $op, $v);
                    }
                }
            });
        }

        return $query;
    }
}
