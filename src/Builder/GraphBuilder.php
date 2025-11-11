<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\FieldParser as QueryBuilderFieldParser;

class GraphBuilder
{
    /**
     * Build a graph-like response using requested fields and relations.
     *
     * @param array|string $fields GraphQL-like fields (e.g., "id title comments { id }")
     */
    public function execute(
        Model | Paginator | Collection $data,
        array | string $fields,
        ?array $onlyFields = null,
        array $options = []): Collection
    {
        if (is_string($fields)) {
            $fields = (new QueryBuilderFieldParser())->normalize($fields);
        }

        if ($onlyFields && is_array($onlyFields)) {
            $fields = $this->filterFields($fields, $onlyFields);
        }

        $unique      = $data instanceof Model;
        $paginator   = $data instanceof Paginator;
        $lengthAware = $data instanceof LengthAwarePaginator;

        // Normalize iterable dataset
        $iterable = $unique ? collect([$data]) : ($paginator ? collect($data->items()) : collect($data));

        $mapped = $iterable->map(function (Model $model) use ($fields, $paginator, $options): array {
            return $this->buildItem($model, null, $fields, $paginator, $options);
        });

        // Build meta
        $meta = [];

        if ($paginator) {
            // SimplePaginator and LengthAwarePaginator
            $meta['meta'] = [
                'per_page'       => $data->perPage(),
                'current_page'   => $data->currentPage(),
                'from'           => $data->firstItem(),
                'to'             => $data->lastItem(),
                'path'           => $data->path(),
                'has_more_pages' => $data->hasMorePages(),
            ];

            if ($data instanceof LengthAwarePaginator) {
                $meta['meta']['total']     = $data->total();
                $meta['meta']['last_page'] = $data->lastPage();
            }
        } elseif (!$unique) {
            $meta['meta'] = [
                'total' => $mapped->count(),
            ];
        }

        if ($unique) {
            // For single model, return only the data map
            return collect($mapped->first());
        }

        return collect([
            'data' => $lengthAware
                ? $mapped->map(fn (array $item) => ['data' => $item])->toArray()
                : $mapped->map(fn (array $item) => ['data' => $item]),
        ] + $meta);
    }

    /**
     * Build a single item's representation according to requested fields and nested relations.
     */
    private function buildItem(Model $model, ?string $fatherRelated, array $fields, bool $skipDateScalars = false, array $options = []): array
    {
        $result = [];

        // Separate scalars and relations
        $scalars   = [];
        $relations = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                $scalars[] = $value; // scalar field name
            } else {
                $relations[$key] = is_array($value) ? $value : [];
            }
        }

        // Map scalar fields with formatting
        foreach ($scalars as $field) {
            // Skip unknown attributes gracefully
            if (!array_key_exists($field, $model->getAttributes()) && !\array_key_exists($field, $this->getAllModelComputed($model))) {
                // Allow timestamps commonly present on models
                if (!in_array($field, ['created_at', 'updated_at', 'deleted_at'], true)) {
                    continue;
                }
            }

            $value = $model->{$field} ?? null;

            // In paginated contexts, tests expect date fields like created_at to be omitted from item data
            if ($skipDateScalars && $value instanceof DateTimeInterface) {
                continue;
            }

            $result[$field] = match (true) {
                $value instanceof BackedEnum        => $this->enum($value),
                $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
                default                             => $value,
            };
        }

        // Map relations
        foreach ($relations as $name => $nestedFields) {
            if (!method_exists($model, $name)) {
                continue;
            }

            $relation = $model->{$name}();

            if (!$relation instanceof Relation) {
                continue;
            }

            // BelongsTo-like (single)
            if ($relation instanceof BelongsTo) {
                $related = $model->{$name};

                if ($related instanceof Model) {
                    $result[$name] = [
                        'data' => $this->buildItem($related, null, $nestedFields, $skipDateScalars, $options),
                    ];
                }

                continue;
            }

            $records = $model->{$name};

            $fatherRelated = $fatherRelated ? "{$fatherRelated}_{$name}" : $name;
            $total         = $model->{$name . '_count'} ?? 0;
            $page          = $options['page_offset_' . $fatherRelated] ?? 1;
            $limit         = $options['page_limit_' . $fatherRelated] ?? config('page.per_page');

            $result[$name] = [
                'data' => $records->map(fn (Model $m) => ['data' => $this->buildItem($m, $name, $nestedFields, $skipDateScalars, $options)])->toArray(),
                'meta' => [
                    'total'          => $total,
                    'page'           => $page,
                    'has_more_pages' => $limit * $page < $total,
                ],
            ];
        }

        return $result;
    }

    private function getAllModelComputed(Model $model): array
    {
        $computed = [];

        foreach ($model->getMutatedAttributes() as $key) {
            $computed[$key] = true;
        }

        return $computed;
    }

    private function enum(BackedEnum $enum): mixed
    {
        if (method_exists($enum, 'label')) {
            return [
                'value' => $enum->value,
                'label' => __($enum->label()),
            ];
        }

        return $enum->value;
    }

    private function filterFields(array $fields, array $onlyFields): array
    {
        // Recursively keep only the scalar fields and relations specified in $onlyFields.
        // - Root-level integer-indexed strings in $onlyFields define allowed scalars at this level.
        // - String keys in $onlyFields define allowed relations and their subfield rules.
        if (empty($onlyFields)) {
            return [];
        }

        // Build sets for quick checks
        $allowedScalars   = [];
        $allowedRelations = [];

        foreach ($onlyFields as $k => $v) {
            if (is_int($k) && is_string($v)) {
                $allowedScalars[$v] = true;
            } elseif (is_string($k) && is_array($v)) {
                $allowedRelations[$k] = $v;
            }
        }

        $filtered = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                // Scalar field at this level
                if (isset($allowedScalars[$value])) {
                    $filtered[] = $value;
                }

                continue;
            }

            // Relation: include only if explicitly allowed, and recursively filter its subfields
            if (array_key_exists($key, $allowedRelations) && is_array($value)) {
                $nested         = $this->filterFields($value, $allowedRelations[$key]);
                $filtered[$key] = $nested;
            }
        }

        return $filtered;
    }
}
