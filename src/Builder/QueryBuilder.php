<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class QueryBuilder
{
    private array $fields      = [];
    private array $filters     = [];
    private array $paginations = [];
    private array $orders      = [];
    private array $withCount   = [];

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
                'field'    => $field->field,
                'offset'   => $field->offset,
                'per_page' => $field->per_page,
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

        foreach ($fields as $key => $value) {
            if (method_exists($model, $value)) {
                unset($fields[$key]);
            }
        }

        if (filled($fields)) {
            $query->select($fields);
        }

        $includes = $this->generateIncludes($model, $this->fields);

        if (filled($includes)) {
            $query->with($includes);
        }

        if (!empty($this->withCount)) {
            $rootCounts = array_values(array_filter(array_keys($this->withCount), fn ($p) => !str_contains($p, '.')));

            if (!empty($rootCounts)) {
                $query->withCount($rootCounts);
            }
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

    private function generateIncludes(Model $model, $fields): array
    {
        $hasNested = false;

        foreach ((array) $fields as $value) {
            if (is_array($value)) {
                $hasNested = true;

                break;
            }
        }

        $paths = $hasNested
            ? $this->nestedDotPaths((array) $fields)
            : array_values(array_filter((array) $fields, fn ($v) => is_string($v) && str_contains($v, '.') || (is_string($v) && method_exists($model, $v))));

        $result    = [];
        $countable = [];

        foreach ($paths as $path) {
            $relation = $this->resolveLastRelation($model, $path);

            if ($relation && !($relation instanceof BelongsTo)) {
                $countable[] = $path;
            }
        }

        $paginations = [];

        foreach ($this->paginations as $pagination) {
            $paginations[$pagination['field']] = [
                'offset'   => $pagination['offset'],
                'per_page' => $pagination['per_page'],
            ];
        }

        $this->withCount = array_fill_keys($countable, true);

        foreach ($paths as $path) {
            $relation = $this->resolveLastRelation($model, $path);

            if ($relation instanceof BelongsTo) {
                $result[] = $path;
            } else {
                $result[$path] = function ($query) use ($path, $countable, $paginations) {
                    $data = data_get($paginations, $path, [
                        'per_page' => config('page.per_page'),
                        'offset'   => 0,
                    ]);

                    $query->limit($data['per_page'])->offset($data['offset']);

                    $childrenCounts = [];
                    $prefix         = $path . '.';
                    $pathDepth      = mb_substr_count($path, '.');

                    foreach ($countable as $c) {
                        if (str_starts_with($c, $prefix) && mb_substr_count($c, '.') === $pathDepth + 1) {
                            $childrenCounts[] = mb_substr($c, mb_strlen($prefix));
                        }
                    }

                    if (!empty($childrenCounts)) {
                        $query->withCount($childrenCounts);
                    }
                };
            }
        }

        return $result;
    }

    private function resolveLastRelation(Model $model, string $path)
    {
        $currentModel = $model;
        $relation     = null;

        foreach (explode('.', $path) as $segment) {
            if (!method_exists($currentModel, $segment)) {
                $relation = null;

                break;
            }

            $relation = $currentModel->{$segment}();

            if (method_exists($relation, 'getRelated')) {
                $currentModel = $relation->getRelated();
            } else {
                break;
            }
        }

        return $relation;
    }
}
