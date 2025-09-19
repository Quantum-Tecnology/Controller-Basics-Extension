<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Support;

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
                        $query->whereNull($qualifiedField);

                        continue;
                    }

                    if ('not-null' === $lowerOp) {
                        $query->whereNotNull($qualifiedField);

                        continue;
                    }

                    if (in_array($op, ['=', '=='], true)) {
                        $query->whereIn($qualifiedField, $values);

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
