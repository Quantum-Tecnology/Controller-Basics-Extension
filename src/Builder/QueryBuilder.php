<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryBuilder
{
    protected array $withCount = [];

    public function execute(Model $model, array $fields = [], array $options = []): Builder
    {
        $query = $model->query();

        $fieldSelected = array_filter($fields, fn ($item) => !is_array($item));

        foreach ($fieldSelected as $key => $value) {
            if (method_exists($model, $value)) {
                unset($fieldSelected[$key]);
            }
        }

        if (filled($fieldSelected)) {
            $query->select($fieldSelected);
        }

        $pagination = $this->extractOptions($options, 'page_offset', 'page_limit');
        $order      = $this->extractOptions($options, 'order_column', 'order_direction');
        $includes   = $this->generateIncludes($model, $fields, $pagination, $order);

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

    private function generateIncludes(Model $model, $fields, array $pagination = [], array $order = []): array
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

        $this->withCount = array_fill_keys($countable, true);

        foreach ($paths as $path) {
            $relation = $this->resolveLastRelation($model, $path);

            if ($relation instanceof BelongsTo) {
                $result[] = $path;
            } else {
                $result[$path] = function ($query) use ($path, $countable, $order, $pagination) {
                    $pathUnderline = str($path)->replace('.', '_')->toString();

                    $paginateInclude = data_get($pagination, $pathUnderline, [
                        'page_limit'  => config('page.per_page'),
                        'page_offset' => 0,
                    ]);

                    $query->limit($paginateInclude['page_limit'])->offset($paginateInclude['page_offset']);

                    $orderInclude = data_get($order, $pathUnderline, [
                        'order_direction' => 'asc',
                    ]);

                    if ($orderInclude['order_column'] ?? null) {
                        $query->orderBy(
                            $orderInclude['order_column'],
                            when('desc' === $orderInclude['order_direction'], fn () => 'desc', fn () => 'asc')
                        );
                    }

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

    private function extractOptions(array $options, ...$items): array
    {
        $result = [];

        if (empty($items)) {
            return $result;
        }

        foreach ($options as $key => $value) {
            // Only process string keys like "page_offset_comments"
            if (!is_string($key)) {
                continue;
            }

            foreach ($items as $item) {
                // Ensure the key pattern matches: <item>_<group>
                $prefix = mb_rtrim((string) $item, '_') . '_';

                if (str_starts_with($key, $prefix)) {
                    $group = mb_substr($key, mb_strlen($prefix));

                    if (false === $group || '' === $group) {
                        continue;
                    }

                    // Initialize group array and assign the item value
                    $result[$group] ??= [];
                    $result[$group][$item] = $value;

                    // Keep a deterministic order of keys (alphabetical),
                    // so test expectations using strict array identity pass
                    ksort($result[$group]);
                }
            }
        }

        return $result;
    }
}
