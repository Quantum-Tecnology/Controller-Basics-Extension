<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Support;

class ApplyFilter
{
    public static function execute($query, array $filters = [])
    {
        $table = $query->getTable();

        foreach ($filters as $field => $items) {
            $query = $query->where(function ($query) use ($table, $field, $items): void {
                if ($table) {
                    $field = $table . '.' . $field;
                }

                foreach ($items as $item) {
                    $op     = $item['operation'] ?? '=';
                    $values = $item['value'] ?? null;

                    if (in_array($op, ['=', '=='])) {
                        $query = $query->whereIn($field, $values);

                        continue;
                    }

                    foreach ($values as $v) {
                        $query = $query->where($field, $op, $v);
                    }
                }
            });
        }

        return $query;
    }
}
