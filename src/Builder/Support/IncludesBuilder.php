<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IncludesBuilder
{
    /**
     * Build the includes array for Eloquent::with() and prepare withCount entries.
     *
     * @param array|string $fields
     * @param array        $withCount Output parameter, will be filled with root/nested count info
     *
     * @return array Includes array compatible with Eloquent::with()
     */
    public static function build(Model $model, $fields, array $filters = [], array $pagination = [], array $order = [], array &$withCount = []): array
    {
        $hasNested = false;

        foreach ((array) $fields as $value) {
            if (is_array($value)) {
                $hasNested = true;

                break;
            }
        }

        $paths = $hasNested
            ? RelationUtils::nestedDotPaths((array) $fields)
            : array_values(array_filter((array) $fields, fn ($v) => is_string($v) && str_contains($v, '.') || (is_string($v) && method_exists($model, $v))));

        $result    = [];
        $countable = [];

        foreach ($paths as $path) {
            $relation = RelationUtils::resolveLastRelation($model, $path);

            if ($relation && !($relation instanceof BelongsTo)) {
                $countable[] = $path;
            }
        }

        // Prepare withCount for all countable relations (store keys); root-level may get filter closures later
        $withCount = array_fill_keys($countable, true);

        // Apply filters to root-level counts if provided
        foreach ($countable as $cPath) {
            if (!str_contains($cPath, '.')) {
                $cKey          = str($cPath)->replace('.', '_')->toString();
                $filterInclude = data_get($filters, $cKey, []);

                if (!empty($filterInclude)) {
                    $withCount[$cPath] = function ($q) use ($filterInclude) {
                        self::applyFilters($q, $filterInclude);
                    };
                }
            }
        }

        foreach ($paths as $path) {
            $relation = RelationUtils::resolveLastRelation($model, $path);

            if (!$relation) {
                continue; // Skip invalid paths that don't resolve to a relation
            }

            if ($relation instanceof BelongsTo) {
                $pathDepth = mb_substr_count($path, '.');

                if (0 === $pathDepth) {
                    // Determine if this root-level BelongsTo has immediate child countable relations
                    $hasImmediateChild = false;
                    $prefix            = $path . '.';

                    foreach ($countable as $c) {
                        if (str_starts_with($c, $prefix) && mb_substr_count($c, '.') === $pathDepth + 1) {
                            $hasImmediateChild = true;

                            break;
                        }
                    }

                    if ($hasImmediateChild) {
                        // Wrap with closure only when there are immediate countable children
                        $result[$path] = function ($query) use ($path, $countable, $filters) {
                            $pathUnderline = str($path)->replace('.', '_')->toString();

                            $childrenCounts = [];
                            $prefix         = $path . '.';
                            $pathDepth      = mb_substr_count($path, '.');

                            foreach ($countable as $c) {
                                if (str_starts_with($c, $prefix) && mb_substr_count($c, '.') === $pathDepth + 1) {
                                    $child = mb_substr($c, mb_strlen($prefix));

                                    $childKey     = str($pathUnderline . '_' . str_replace('.', '_', $child))->toString();
                                    $childFilters = data_get($filters, $childKey, []);

                                    if (!empty($childFilters)) {
                                        $childrenCounts[$child] = function ($q) use ($childFilters) {
                                            IncludesBuilder::applyFilters($q, $childFilters);
                                        };
                                    } else {
                                        $childrenCounts[] = $child;
                                    }
                                }
                            }

                            if (!empty($childrenCounts)) {
                                $query->withCount($childrenCounts);
                            }
                        };
                    } else {
                        $result[] = $path; // plain string include
                    }
                } else {
                    $result[] = $path; // deeper BelongsTo
                }
            } else {
                $result[$path] = function ($query) use ($path, $countable, $filters, $order, $pagination) {
                    $pathUnderline = str($path)->replace('.', '_')->toString();

                    $paginateInclude = data_get($pagination, $pathUnderline, [
                        'page_limit'  => config('page.per_page'),
                        'page_offset' => 0,
                    ]);

                    $filterInclude = data_get($filters, $pathUnderline, []);

                    IncludesBuilder::applyFilters($query, $filterInclude)
                        ->limit($paginateInclude['page_limit'])
                        ->offset($paginateInclude['page_offset']);

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
                            $child        = mb_substr($c, mb_strlen($prefix));
                            $childKey     = str($pathUnderline . '_' . str_replace('.', '_', $child))->toString();
                            $childFilters = data_get($filters, $childKey, []);

                            if (!empty($childFilters)) {
                                $childrenCounts[$child] = function ($q) use ($childFilters) {
                                    IncludesBuilder::applyFilters($q, $childFilters);
                                };
                            } else {
                                $childrenCounts[] = $child;
                            }
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

    private static function applyFilters($query, array $filters = [])
    {
        foreach ($filters as $field => $items) {
            $query = $query->where(function ($query) use ($field, $items) {
                foreach ($items as $item) {
                    $op     = $item['operation'] ?? '=';
                    $values = $item['value'] ?? null;

                    $val   = $values[0] ?? null;
                    $query = $query->where($field, $op, $val);
                }
            });
        }

        return $query;
    }
}
