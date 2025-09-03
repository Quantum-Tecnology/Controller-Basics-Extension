<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Enum\QueryBuilderType;
use QuantumTecnology\ControllerBasicsExtension\Support\FilterSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

final readonly class BuilderQuery
{
    public function __construct(
        private FilterSupport $filterSupport,
        private PaginationSupport $paginationSupport,
    ) {
    }

    public function execute($model, array $fields = [], array $filters = [], array $pagination = [])
    {
        $filters = $this->filterSupport->parse($filters);

        $query     = $this->filters($model->newQuery(), $filters['[__model__]'] ?? []);
        $with      = [];
        $includes  = $this->nestedDotPaths($fields);
        $withCount = [];

        foreach ($includes as $include) {
            $changePointUnderline = str_replace('.', '_', $include);
            $filter               = $filters[$changePointUnderline] ?? [];

            if (!str_contains((string) $include, '.')) {
                $withCount[$include] = fn ($query) => $this->filters($query, $filter);
            }

            $paginate = data_get($pagination, str_replace('.', '_', $include));

            $limit = $this->paginationSupport->calculatePerPage($paginate['per_page'] ?? null, $include);
            $page  = $paginate['page'] ?? 1;

            $withCountChildren = [];
            $includeChildren   = $this->getIncludesWithCount($includes, $include);
            $includeCamel      = Str::camel($include);

            foreach ($includeChildren as $child) {
                $childCamel                     = Str::camel($child);
                $filterChildren                 = $filters["{$include}_{$child}"] ?? [];
                $withCountChildren[$childCamel] = fn ($query) => $this->filters($query, $filterChildren);
            }

            $offset = ($page - 1) * $limit;

            $relation    = method_exists($model, $include) ? $model->$include() : null;
            $isBelongsTo = $relation instanceof BelongsTo;

            $with[$includeCamel] = fn ($query) => $isBelongsTo
                ? $this->filters($query->withCount($withCountChildren), $filter)
                : $this->filters($query->withCount($withCountChildren), $filter)
                    ->offset($offset)
                    ->limit((string) $limit);
        }

        $query->withCount($withCount)->with($with);

        return $query;
    }

    private function nestedDotPaths(array $fields, string $prefix = ''): array
    {
        $paths = [];

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $current = '' !== $prefix && '0' !== $prefix ? "$prefix.$key" : $key;
                $paths[] = $current;
                $paths   = array_merge($paths, $this->nestedDotPaths($value, $current));
            }
        }

        return $paths;
    }

    private function filters($query, array $filters = [])
    {
        if (blank($filters)) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        foreach ($filters as $column => $value) {
            foreach ($value as $operator => $data) {
                if ('byFilter' === $column) {
                    foreach ($data as $itemData) {
                        $dataItems = explode(';', (string) $itemData);

                        $query->where(function ($query) use ($dataItems, $operator): void {
                            foreach ($dataItems as $item) {
                                $tItem = mb_trim($item);

                                if (filled($item)) {
                                    foreach (explode(';', (string) $operator) as $column) {
                                        $query->orWhereLike($column, "{$tItem}%");
                                    }
                                }
                            }
                        });
                    }

                    continue;
                }

                if (filled($data) && ('true' === $data[0] || 'false' === $data[0])) {
                    $data[0] = filter_var($data[0], FILTER_VALIDATE_BOOLEAN);
                }

                if (str_starts_with($column, 'by')) {
                    $query->{$column}(collect($data));

                    continue;
                }

                if (isset($data[0]) && $data[0] instanceof BackedEnum) {
                    match (true) {
                        QueryBuilderType::Null === $data[0]    => $query->whereNull("{$table}.{$column}"),
                        QueryBuilderType::NotNull === $data[0] => $query->whereNotNull("{$table}.{$column}"),
                    };

                    continue;
                }

                match ($operator) {
                    '='    => when(is_bool($data[0]), $query->where("{$table}.{$column}", $data), $query->whereIn("{$table}.{$column}", $data)),
                    'like' => $query->where(function ($query) use ($table, $column, $data): void {
                        foreach ($data as $item) {
                            $query->orWhereLike("{$table}.{$column}", "%{$item}%");
                        }
                    }),
                    default => $query->where("{$table}.{$column}", $operator, $data[0]),
                };
            }
        }

        return $query;
    }

    private function getIncludesWithCount(array $includes, string $include): array
    {
        return collect($includes)
            ->filter(fn ($item) => Str::startsWith($item, $include . '.'))
            ->map(fn ($item) => Str::after($item, $include . '.'))
            ->values()
            ->all();
    }
}
