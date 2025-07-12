<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Presenters;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\QueryBuilder\GenerateQuery;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginateSupport;

final class GenericPresenter
{
    public function __construct(protected PaginateSupport $paginateSupport)
    {
    }

    public function transform(
        Model $model,
        array $options = [],
        string $fields = ''
    ): array {
        $pagination     = $this->paginateSupport->extractPagination($options);
        $internalFields = $this->parseFields($fields);
        $includes       = $this->getIncludesByFields($fields);

        $output = [];

        $selfFields = $internalFields['__self'] ?? [];

        foreach (get_object_vars($model) as $key => $value) {
            if (Str::startsWith($key, 'can_') && !in_array($key, $selfFields, true)) {
                $selfFields[] = $key;
            }
        }

        $groupedFields = $this->groupNestedFields($selfFields);

        foreach ($groupedFields as $field => $nested) {
            $value = data_get($model, $field);

            if (is_array($value) && true !== $nested) {
                $output[$field] = collect($value)->only($nested)->toArray();
            } else {
                $output[$field] = match (true) {
                    $value instanceof CarbonImmutable => $value->toDateTimeString(),
                    $value instanceof BackedEnum      => [
                        'type'  => 'enum',
                        'value' => $value->value,
                        'key'   => $value->name,
                    ],
                    default => $value
                };
            }
        }

        foreach ($includes as $key => $includeValue) {
            $segments = explode('.', is_int($key) ? $includeValue : $key);
            $this->handleIncludePath($model, $output, $internalFields, $pagination, $segments, []);
        }

        return collect($output)
            ->partition(fn ($value, $key) => str_starts_with($key, 'can_'))
            ->pipe(function ($partitions) {
                [$can, $rest] = $partitions;
                $metaActions  = $can->all();

                if (empty($metaActions)) {
                    return $rest->toArray();
                }

                return $rest
                    ->put('actions', $metaActions)
                    ->toArray();
            });
    }

    public function getIncludes(
        Model $model,
        string $fields,
        array $pagination,
        array $filters = [],
        ?object $classCallable = null,
        ?string $action = null,
    ): array {
        $includes            = [];
        $processedPaths      = [];
        $relationsFromFields = $this->getIncludesByFields($fields);

        foreach (array_unique($relationsFromFields) as $relationPath) {
            $segments     = explode('.', $relationPath);
            $currentModel = $model;
            $pathSoFar    = [];

            foreach ($segments as $segment) {
                $pathSoFar[] = $segment;
                $currentPath = implode('.', $pathSoFar);
                $method      = Str::camel($segment);

                if (!method_exists($currentModel, $method)) {
                    break;
                }

                $relation = $currentModel->$method();

                if (!($relation instanceof Relations\Relation)) {
                    break;
                }

                if ($relation instanceof Relations\HasMany) {
                    $limit = $this->paginateSupport->calculatePerPage(
                        (string) ($pagination[$currentPath]['per_page'] ?? ''),
                        $currentPath
                    );

                    $page    = ($pagination[$currentPath]['page'] ?? 1);
                    $perPage = ($pagination[$currentPath]['per_page'] ?? $limit);
                    $offset  = ($page - 1) * $perPage;

                    $currentPathUnderline = str_replace('.', '_', $currentPath);
                    $filterOfInclude      = $filters[$currentPathUnderline] ?? [];

                    if (!isset($processedPaths[$currentPath])) {
                        $includes[$currentPath] = fn ($query) => ($this->getQueryCallable(
                            $query,
                            $classCallable,
                            $filterOfInclude,
                            $action,
                            $currentPath,
                        ) ?: $query)
                            ->offset($offset)
                            ->limit($limit);
                        $processedPaths[$currentPath] = true;
                    }

                } else {
                    if (!in_array($currentPath, $includes, true) && !isset($processedPaths[$currentPath])) {
                        $includes[]                   = $currentPath;
                        $processedPaths[$currentPath] = true;
                    }
                }

                if ($relation instanceof Relations\BelongsTo) {
                    $childFields = collect($relationsFromFields)
                        ->filter(fn ($p) => Str::startsWith($p, $currentPath . '.') && mb_substr_count($p, '.') > mb_substr_count($currentPath, '.'))
                        ->map(fn ($p) => Str::after($p, $currentPath . '.'))
                        ->unique()
                        ->values()
                        ->all();

                    if ($childFields) {
                        $queryChildFields = [];

                        foreach ($childFields as $child) {
                            $currentPathChild          = $currentPath . '.' . $child;
                            $currentPathChildUnderline = str_replace('.', '_', $currentPathChild);
                            $filterOfInclude           = $filters[$currentPathChildUnderline] ?? [];
                            $queryChildFields[$child]  = fn ($query) => $this->getQueryCallable(
                                $query,
                                $classCallable,
                                $filterOfInclude,
                                $action,
                                $currentPathChild
                            ) ?: $query;
                        }

                        $includes[$currentPath] = fn ($query) => $query->withCount($queryChildFields);

                    } else {
                        $includes[] = $currentPath;
                    }
                }

                $currentModel = $relation->getRelated();
            }
        }

        return $includes;
    }

    public function getWithCount(Model $model, array $allIncludes): array
    {
        $withCount = [];

        foreach ($allIncludes as $key => $value) {
            $relationPath = is_int($key) ? $value : $key;

            if (str_contains($relationPath, '.')) {
                continue;
            }

            $method = Str::camel($relationPath);

            if (method_exists($model, $method)) {
                $relation = $model->$method();

                if ($relation instanceof Relations\HasMany
                    || $relation instanceof Relations\HasOne) {
                    $withCount[] = $relationPath;
                }
            }
        }

        return array_unique($withCount);
    }

    private function getIncludesByFields(string $fields): array
    {
        $relationsFromFields = [];

        $fieldsArray = array_filter(array_map('trim', explode(',', $fields)));

        foreach ($fieldsArray as $field) {
            if (str_contains($field, 'actions.')) {
                continue;
            }

            if (Str::contains($field, '.')) {
                $parts = explode('.', $field);
                $path  = '';

                foreach ($parts as $index => $part) {
                    $path = '' === $path ? $part : $path . '.' . $part;

                    if ($index < count($parts) - 1) {
                        $relationsFromFields[] = $path;
                    }
                }
            }
        }

        return $relationsFromFields;
    }

    private function handleIncludePath($model, &$output, $fields, $pagination, $segments, $pathSoFar): void
    {
        $relation = array_shift($segments);
        $camelRel = Str::camel($relation);
        $fullPath = implode('.', [...$pathSoFar, $relation]);

        if (!method_exists($model, $camelRel)) {
            return;
        }

        $relationObject = $model->$camelRel();

        if ($relationObject instanceof Relations\HasMany) {
            $related = $model->$camelRel;

            if (!$related) {
                return;
            }

            $countAttribute = $relation . '_count';

            $output[$relation] = [
                'data' => $related->map(function ($item) use ($fields, $fullPath, $pagination) {
                    return $this->transform($item, [
                        'include'    => '',
                        'pagination' => $pagination,
                    ], fields: $this->transformArrayToString($fields[$fullPath] ?? []));
                }),
                'meta' => [
                    'total_items'    => $model->{$countAttribute} ?? null,
                    'items_returned' => $related->count(),
                    'has_more_pages' => ($model->{$countAttribute} ?? 0) > ($pagination[$fullPath]['page'] ?? 1) * ($pagination[$fullPath]['per_page'] ?? $related->count()),
                ],
            ];

        } elseif ($relationObject instanceof Relations\BelongsTo) {
            $related = $model->$camelRel;

            if (!$related) {
                return;
            }

            $output[$relation] = $this->transform($related, [
                'include'    => '',
                'pagination' => $pagination,
            ], fields: $this->transformArrayToString($fields[$fullPath] ?? []));
        }

        if (!empty($segments)) {
            if (isset($output[$relation]['data'])) {
                $output[$relation]['data'] = collect($output[$relation]['data'])->map(function ($item) use ($segments, $fields, $pagination, $pathSoFar, $relation) {
                    $this->handleIncludePath((object) $item, $item, $fields, $pagination, $segments, [...$pathSoFar, $relation]);

                    return $item;
                });
            } else {
                $this->handleIncludePath((object) $output[$relation], $output[$relation], $fields, $pagination, $segments, [...$pathSoFar, $relation]);
            }
        }

    }

    private function parseFields(string $raw): array
    {
        $result = [];

        foreach (explode(',', $raw) as $field) {
            if (!$field = mb_trim($field)) {
                continue;
            }

            if (str_contains($field, '.')) {
                [$relationPath, $nested] = explode('.', $field, 2);
                $result[$relationPath][] = $nested;
            } else {
                $result['__self'][] = $field;
            }
        }

        return $result;
    }

    private function groupNestedFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                [$parent, $child]  = explode('.', $field, 2);
                $result[$parent][] = $child;
            } else {
                $result[$field] = true;
            }
        }

        return $result;
    }

    private function transformArrayToString(array | string | null $fields): string
    {
        if (blank($fields)) {
            return '';
        }

        if (is_array($fields)) {
            return implode(',', array_map('trim', $fields));
        }

        return $fields;
    }

    private function getQueryCallable(
        $query,
        ?object $classCallable,
        array $filters,
        ?string $action,
        string $relationPath
    ) {
        if ($classCallable) {
            if ($action) {
                $method = 'query' . Str::studly(str_replace('.', '_', $action . ' ' . $relationPath));

                if (method_exists($classCallable, $method)) {
                    $classCallable->{$method}($query);
                }

                $method = 'query' . Str::studly(str_replace('.', '_', 'only ' . $action . $relationPath));

                if (method_exists($classCallable, $method)) {
                    $classCallable->{$method}($query);
                }
            }

            $method = 'query' . Str::studly(str_replace('.', '_', $relationPath));

            if (method_exists($classCallable, $method)) {
                $classCallable->{$method}($query);
            }
        }

        if (filled($filters)) {
            app(GenerateQuery::class)->addWhereWithFilters($query, $filters);
        }

        return null;
    }
}
