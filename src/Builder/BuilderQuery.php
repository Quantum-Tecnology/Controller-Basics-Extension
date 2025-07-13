<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

final class BuilderQuery
{
    public function execute(Model $model, array $fields, array $filters = [], array $pagination = []): Builder
    {
        $paginationSupport = app(PaginationSupport::class);

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

            $paginate = data_get($pagination, $include);
            $limit    = $paginationSupport->calculatePerPage($paginate['per_page'] ?? null, $include);
            $page     = $paginate['page'] ?? 1;

            $withCountChildren = [];
            $includeChildren   = $this->getIncludesWithCount($includes, $include);
            $includeCamel      = Str::camel($include);

            foreach ($includeChildren as $child) {
                $childCamel = Str::camel($child);

                $withCountChildren[$childCamel] = fn ($query) => $this->filters($query, $filters[$child] ?? []);
            }

            $offset = ($page - 1) * $limit;

            $with[$includeCamel] = fn ($query) => $this->filters($query->withCount($withCountChildren), $filter)
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
                match ($operator) {
                    '='     => $query->whereIn("{$table}.{$column}", $data),
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
