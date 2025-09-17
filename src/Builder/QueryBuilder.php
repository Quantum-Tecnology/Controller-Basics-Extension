<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class QueryBuilder
{
    private array $fields      = [];
    private array $filters     = [];
    private array $paginations = [];
    private array $orders      = [];

    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function filters(Request\Filter $filter): self
    {
        foreach ($filter->getData() as $field) {
            $this->filters[] = [
                'field'     => $field->column,
                'operation' => $field->operation,
            ];
        }

        return $this;
    }

    public function paginations(Request\Pagination $pagination): self
    {
        foreach ($pagination->getData() as $field) {
            $this->paginations[] = [
                'field'    => $field->column,
                'limit'    => $field->direction,
                'per_page' => $field->direction,
            ];
        }

        return $this;
    }

    public function orders(Request\Order $order): self
    {
        foreach ($order->getData() as $field) {
            $this->orders[] = [
                'column'    => $field->column,
                'direction' => $field->direction,
            ];
        }

        return $this;
    }

    public function execute(Model $model): Builder
    {
        $query = $model->query();

        $fields = array_filter($this->fields, fn ($item) => !is_array($item));

        if (filled($fields)) {
            $query->select($fields);
        }

        $withRelation = [];
        $withCount    = [];

        $includes = $this->generateIncludes($model, $this->nestedDotPaths($this->fields));
        dd($includes);

        //        foreach ($includes as $include) {
        //            if (!str_contains((string) $include, '.')) {
        //                $withCount[$include] = fn ($query) => $this->executeFilters($query, $include);
        //            }
        //
        //            $relation          = method_exists($model, $include) ? $model->$include() : null;
        //            $isBelongsTo       = $relation instanceof BelongsTo;
        //            $includeCamel      = str($include)->camel()->toString();
        //            $includeChildren   = $this->getIncludesWithCount($includes, $include);
        //            $withCountChildren = [];
        //
        //            foreach ($includeChildren as $child) {
        //                $childCamel                     = str($child)->camel()->toString();
        //                $withCountChildren[$childCamel] = fn ($query) => $this->executeFilters($query, "{$include}_{$child}");
        //            }
        //
        //            $withRelation[$includeCamel] = fn ($query) => $isBelongsTo
        //                ? $this->executeFilters($query->withCount($withCountChildren), null)
        //                : $this->executeFilters($query->withCount($withCountChildren), null)
        //                    ->offset(0)
        //                    // ->when(filled($dataOrder['column'] ?? null), fn ($query) => $query->orderBy($dataOrder['column'], $dataOrder['direction'] ?? 'asc'))
        //                    ->limit(10);
        //        }

        if (filled($withRelation)) {
            $query->with($withRelation);
        }

        if (filled($withCount)) {
            $query->withCount($withCount);
        }

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

    private function getIncludesWithCount(array $includes, string $include): array
    {
        return collect($includes)
            ->filter(fn ($item) => str($item)->startsWith($include . '.'))
            ->map(fn ($item) => str($item)->after($include . '.'))
            ->values()
            ->all();
    }

    private function executeFilters($query, ?string $include)
    {
        return $query;
    }

    private function generateIncludes(Model $model, $includes): array
    {
        return [];
        //        $response = [];
        //
        //        foreach ($includes as $include) {
        //            $method = method_exists($model, $include) ? $model->$include() : null;
        //
        //            if ($method instanceof BelongsTo || $method instanceof HasOne) {
        //                $response[] = $include;
        //            }
        //
        //            if (str($include)->contains('.')) {
        //
        //            }
        //        }
        //
        //        return $response;
    }
}
