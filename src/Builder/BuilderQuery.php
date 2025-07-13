<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

class BuilderQuery
{
    public function execute(Model $model, array $fields, array $pagination = []): Builder
    {
        $paginationSupport = app(PaginationSupport::class);

        $query    = $model->newQuery();
        $with     = [];
        $includes = $this->nestedDotPaths($fields);

        foreach ($includes as $include) {

            $paginate = data_get($pagination, $include);
            $limit    = $paginationSupport->calculatePerPage($paginate['per_page'] ?? null, $include);

            $with[$include] = fn ($query) => $query->limit((string) $limit);
        }

        $query->with($with);

        return $query;
    }

    protected function nestedDotPaths(array $fields, string $prefix = ''): array
    {
        $paths = [];

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $current = $prefix ? "$prefix.$key" : $key;
                $paths[] = $current;
                $paths   = array_merge($paths, $this->nestedDotPaths($value, $current));
            }
        }

        return $paths;
    }
}
