<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryBuilder
{
    protected array $withCount = [];

    public function execute(Model $model, string | array $fields = [], array $options = []): Builder
    {
        $query = $model->query();

        if (is_string($fields)) {
            $fields = $this->normalizeFieldsFromArray($fields);
        }

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
        $filters    = $this->extractFilters($model, $options);
        $includes   = $this->generateIncludes($model, $fields, $filters, $pagination, $order);

        if (filled($includes)) {
            $query->with($includes);
        }

        if (!empty($this->withCount)) {
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

            if (!empty($counts)) {
                $query->withCount($counts);
            }
        }

        return $query;
    }

    protected function extractFilters(Model $model, array $data, string $prefixKey = 'filter'): array
    {
        $filters = [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            // Expected patterns (using custom prefixKey):
            // - {prefixKey}(field)
            // - {prefixKey}(field,op)
            // - {prefixKey}_comments(field)
            // - {prefixKey}_comments(field,op)
            // - {prefixKey}_comments_likes(field,op)
            if (!str_starts_with($key, $prefixKey)) {
                continue;
            }

            // Extract suffix after the prefix and the inner content between parentheses
            $openParenPos  = mb_strpos($key, '(');
            $closeParenPos = mb_strrpos($key, ')');

            if (false === $openParenPos || false === $closeParenPos || $closeParenPos < $openParenPos) {
                continue;
            }

            $prefix = mb_substr($key, 0, $openParenPos); // e.g., 'filter' or 'filter_comments'
            $inside = mb_substr($key, $openParenPos + 1, $closeParenPos - $openParenPos - 1); // e.g., 'id' or 'title,~'

            // Determine relation group: root model or relation path indicated by underscores
            if ($prefix === $prefixKey) {
                $group = get_class($model);
            } else {
                // remove leading '{prefixKey}_'
                $expected = $prefixKey . '_';
                $group    = str_starts_with($prefix, $expected) ? mb_substr($prefix, mb_strlen($expected)) : '';

                if ('' === $group) {
                    $group = get_class($model);
                }
            }

            // Parse field and optional operation split by the first comma
            $field     = $inside;
            $operation = '=';

            if (str_contains($inside, ',')) {
                [$field, $op] = array_pad(explode(',', $inside, 2), 2, null);
                $field        = mb_trim((string) $field);
                $operation    = mb_trim((string) ($op ?? '='));

                if ('' === $operation) {
                    $operation = '=';
                }
            } else {
                $field = mb_trim($field);

                // Special rule: if field starts with 'by' and no explicit operation, use 'by'
                if (str_starts_with($field, 'by')) {
                    $operation = 'by';
                }
            }

            if ('' === $field) {
                continue;
            }

            // If value is null and no explicit null/not-null op, ignore this filter
            if (is_null($value) && !in_array(mb_strtolower($operation), ['null', 'not-null'], true)) {
                continue;
            }

            // Initialize structures
            $filters[$group] ??= [];
            $filters[$group][$field] ??= [];

            // Handle special null/not-null operations: store value as null (not a collection)
            if (in_array(mb_strtolower($operation), ['null', 'not-null'], true)) {
                $filters[$group][$field][] = [
                    'operation' => $operation,
                    'value'     => null,
                ];

                continue;
            }

            // Normalize value into a Laravel collection. If it's a pipe-separated
            // string (e.g., "1|2|3"), split into an array of strings; otherwise,
            // wrap the single value preserving its original type.
            if (is_string($value) && str_contains($value, '|')) {
                $normalized = explode('|', $value);
            } else {
                $normalized = [$value];
            }

            $filters[$group][$field][] = [
                'operation' => $operation,
                'value'     => collect($normalized),
            ];
        }

        return $filters;
    }

    private function generateIncludes(Model $model, $fields, array $filters = [], array $pagination = [], array $order = []): array
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

        // Prepare withCount for all countable relations (store keys); root-level may get filter closures later
        $this->withCount = array_fill_keys($countable, true);

        // Apply filters to root-level counts if provided
        foreach ($countable as $cPath) {
            if (!str_contains($cPath, '.')) {
                $cKey          = str($cPath)->replace('.', '_')->toString();
                $filterInclude = data_get($filters, $cKey, []);

                if (!empty($filterInclude)) {
                    $this->withCount[$cPath] = function ($q) use ($filterInclude) {
                        $this->filters($q, $filterInclude);
                    };
                }
            }
        }

        foreach ($paths as $path) {
            $relation = $this->resolveLastRelation($model, $path);

            if ($relation instanceof BelongsTo) {
                $result[] = $path;
            } else {
                $result[$path] = function ($query) use ($path, $countable, $filters, $order, $pagination) {
                    $pathUnderline = str($path)->replace('.', '_')->toString();

                    $paginateInclude = data_get($pagination, $pathUnderline, [
                        'page_limit'  => config('page.per_page'),
                        'page_offset' => 0,
                    ]);

                    $filterInclude = data_get($filters, $pathUnderline, []);

                    $this->filters($query, $filterInclude)->limit($paginateInclude['page_limit'])->offset($paginateInclude['page_offset']);

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
                            $child = mb_substr($c, mb_strlen($prefix));
                            // Determine filters for the child relation count, e.g., comments_likes
                            $childKey     = str($pathUnderline . '_' . str_replace('.', '_', $child))->toString();
                            $childFilters = data_get($filters, $childKey, []);

                            if (!empty($childFilters)) {
                                $childrenCounts[$child] = function ($q) use ($childFilters) {
                                    $this->filters($q, $childFilters);
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

                    if ('' === $group) {
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

    private function filters($query, array $filters = [])
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

    private function normalizeFieldsFromArray(string $fields): array
    {
        // Tokenize into identifiers and braces, ignore other characters
        preg_match_all('/[A-Za-z0-9_]+|\{|\}/u', $fields, $matches);
        $tokens = $matches[0] ?? [];

        // Root accumulator and path-based navigation to avoid reference loss
        $root = [];
        $path = [];

        // Helper to get a reference to the current context by path
        $ctx = function &() use (&$root, &$path) {
            $ref = &$root;

            foreach ($path as $seg) {
                $ref = &$ref[$seg];
            }

            return $ref;
        };

        // Current context by reference
        $current = &$ctx();

        $canUnwindToRoot = false; // after closing a child under a parent

        $i = 0;
        $n = count($tokens);

        $pushCurrent = function (&$current, array $path, $name) {
            // At nested level (i.e., path not empty), treat non-id as relation with empty array
            if (!empty($path) && 'id' !== $name) {
                $current[$name] = [];
            } else {
                $current[] = $name;
            }
        };

        while ($i < $n) {
            $tok = $tokens[$i];

            if ('{' === $tok) {
                // Begin a new nested block on the last added relation
                $lastKey = null;

                foreach (array_reverse(array_keys($current)) as $k) {
                    if (!is_int($k) && is_array($current[$k])) {
                        $lastKey = $k;

                        break;
                    }
                }

                if (null === $lastKey) {
                    ++$i;

                    continue;
                }

                // Dive into the relation
                $path[]          = $lastKey;
                $current         = &$ctx();
                $canUnwindToRoot = false;
                ++$i;

                continue;
            }

            if ('}' === $tok) {
                // Pop one level if possible
                if (!empty($path)) {
                    array_pop($path);
                    $current = &$ctx();
                    // If we're now exactly one level deep, allow unwinding to root on next identifier
                    $canUnwindToRoot = 1 === count($path);
                }
                ++$i;

                continue;
            }

            // Identifier
            $name = $tok;
            $next = $tokens[$i + 1] ?? null;

            // If we've just closed a child under a parent, treat the next item as root-level
            if (!empty($path) && $canUnwindToRoot) {
                $path            = [];
                $current         = &$ctx();
                $canUnwindToRoot = false;
            }

            if ('{' === $next) {
                // Prepare a relation key for the upcoming block
                $current[$name] = [];
                ++$i;

                continue;
            }

            // Otherwise, push as scalar or nested relation (heuristic)
            $pushCurrent($current, $path, $name);
            $canUnwindToRoot = false;
            ++$i;
        }

        return $root;
    }
}
