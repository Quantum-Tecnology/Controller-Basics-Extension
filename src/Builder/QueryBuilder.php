<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use QuantumTecnology\ControllerBasicsExtension\Builder\Support\FieldParser;
use QuantumTecnology\ControllerBasicsExtension\Builder\Support\FilterParser;
use QuantumTecnology\ControllerBasicsExtension\Builder\Support\IncludesBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\Support\RelationUtils;

class QueryBuilder
{
    protected array $withCount = [];

    /**
     * Backward-compatibility wrapper kept for tests and external callers.
     * Accepts either a GraphQL-like string or the already-normalized array.
     */
    public function normalizeFieldsFromArray(string | array $fields): array
    {
        if (is_string($fields)) {
            return FieldParser::normalize($fields);
        }

        return $fields;
    }

    public function execute(Model $model, string | array $fields = [], array $options = []): EloquentBuilder
    {
        $query = $model->query();

        if (is_string($fields)) {
            $fields = FieldParser::normalize($fields);
        }

        // Collect root-level scalar fields requested
        $fieldSelected = array_filter($fields, fn ($item): bool => !is_array($item));

        // Remove any relation names that might appear as scalars
        foreach ($fieldSelected as $key => $value) {
            if (method_exists($model, $value)) {
                unset($fieldSelected[$key]);
            }
        }

        // Extract include/filters/pagination before finalizing select so we can add FK for BelongsTo
        $pagination = $this->extractOptions($options, 'page_offset', 'page_limit');
        $order      = $this->extractOptions($options, 'order_column', 'order_direction');
        $filters    = FilterParser::extract($model, $options);
        $includes   = IncludesBuilder::build($model, $fields, $filters, $pagination, $order, $this->withCount);

        // If there are BelongsTo includes, make sure to select their foreign keys on the parent
        foreach ($includes as $key => $val) {
            $path = is_int($key) ? $val : $key;

            if (!is_string($path)) {
                continue;
            }
            $relation = RelationUtils::resolveLastRelation($model, $path);

            if ($relation instanceof BelongsTo) {
                $fk = $relation->getForeignKeyName();

                if (!in_array($fk, $fieldSelected, true)) {
                    $fieldSelected[] = $fk;
                }
            }
        }

        // Apply select after enriching with any required foreign keys
        if (filled($fieldSelected)) {
            $query->select($fieldSelected);
        }

        if (filled($includes)) {
            $query->with($includes);
        }

        if ([] !== $this->withCount) {
            // Only pass root-level counts to the query, preserving closures; nested counts are handled within relation closures
            $counts = [];

            foreach ($this->withCount as $k => $v) {
                if (!str_contains($k, '.')) {
                    if (true === $v) {
                        $counts[] = $k;
                    } else {
                        $counts[$k] = $v; // closure or constraints
                    }
                }
            }

            if ([] !== $counts) {
                $query->withCount($counts);
            }
        }

        return $query;
    }

    protected function extractFilters(Model $model, array $data, string $prefixKey = 'filter'): array
    {
        return FilterParser::extract($model, $data, $prefixKey);
    }

    /**
     * Backward-compatibility wrapper for generating includes and withCount.
     * Delegates to IncludesBuilder while updating $this->withCount by reference.
     */
    private function generateIncludes(Model $model, array | string $fields, array $filters = [], array $pagination = [], array $order = []): array
    {
        return IncludesBuilder::build($model, $fields, $filters, $pagination, $order, $this->withCount);
    }

    private function extractOptions(array $options, string ...$items): array
    {
        $result = [];

        if ([] === $items) {
            return $result;
        }

        foreach ($options as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            foreach ($items as $item) {
                $prefix = mb_rtrim($item, '_') . '_';

                if (str_starts_with($key, $prefix)) {
                    $group = mb_substr($key, mb_strlen($prefix));

                    if ('' === $group) {
                        continue;
                    }
                    $result[$group] ??= [];
                    $result[$group][$item] = $value;
                    ksort($result[$group]);
                }
            }
        }

        return $result;
    }
}
