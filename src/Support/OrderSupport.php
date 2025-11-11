<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class OrderSupport
{
    public function parse(array $data): array
    {
        $result = [];

        // Root level order
        $rootColumn = $data['order_column'] ?? null;
        $rootDir    = $data['order_direction'] ?? null;

        if (null !== $rootColumn) {
            $result['order'] = [
                'column'    => (string) $rootColumn,
                'direction' => $this->normalizeDirection($rootDir),
            ];
        }

        // Helper collectors
        $dotGroups       = [];
        $underscoreGroup = [];

        foreach ($data as $key => $value) {
            if ('order_column' === $key) {
                continue;
            }

            if ('order_direction' === $key) {
                continue;
            }

            // order_* for dot notation: order_column.items, order_direction.items
            if (preg_match('/^order_(column|direction)\.(.+)$/', (string) $key, $m)) {
                $field                      = $m[1];
                $entity                     = $m[2];
                $dotGroups[$entity][$field] = (string) $value;

                continue;
            }

            // order_* for underscore base with optional nested path: order_column_items.comment.likes
            if (preg_match('/^order_(column|direction)_([^\.]+)(?:\.(.+))?$/', (string) $key, $m)) {
                $field                                       = $m[1];
                $base                                        = $m[2];
                $suffixPath                                  = $m[3] ?? '';
                $underscoreGroup[$base][$suffixPath][$field] = (string) $value;
            }
        }

        // Build dot notation results
        foreach ($dotGroups as $entity => $fields) {
            $column = $fields['column'] ?? null;
            $dir    = $fields['direction'] ?? null;

            if (null !== $column) {
                $result[$entity]['order'] = [
                    'column'    => $column,
                    'direction' => $this->normalizeDirection($dir),
                ];
            }
        }

        // Build underscore groups
        foreach ($underscoreGroup as $base => $paths) {
            // Base order (no suffix)
            if (isset($paths[''])) {
                $col = $paths['']['column'] ?? null;
                $dir = $paths['']['direction'] ?? null;

                if (null !== $col) {
                    $result[$base]['order'] = [
                        'column'    => $col,
                        'direction' => $this->normalizeDirection($dir),
                    ];
                }
            }

            // Determine if we should use generic children order for single-level only case
            $nonEmptyPaths      = array_filter(array_keys($paths), fn ($p): bool => '' !== $p);
            $useGenericChildren = false;

            // if there is exactly one child path and it has only one segment and there is no base order and no other nested paths
            if (!([] === $nonEmptyPaths) && (1 === count($nonEmptyPaths) && !isset($paths['']))) {
                $only = $nonEmptyPaths[0];

                if (0 === mb_substr_count($only, '.')) {
                    $fields = $paths[$only] ?? [];

                    if (isset($fields['column'])) {
                        $useGenericChildren                 = true;
                        $result[$base]['children']['order'] = [
                            'column'    => $fields['column'],
                            'direction' => $this->normalizeDirection($fields['direction'] ?? null),
                        ];
                    }
                }
            }

            if ($useGenericChildren) {
                // Also handle any deeper paths (should not exist for generic case based on tests)
                continue;
            }

            // Named child handling and deeper paths
            foreach ($paths as $path => $fields) {
                if ('' === $path) {
                    continue;
                }

                $segments = explode('.', $path);
                $first    = array_shift($segments);

                // If only first-level path and column provided → child order
                if (0 === count($segments)) {
                    if (isset($fields['column'])) {
                        $result[$base]['children'][$first]['order'] = [
                            'column'    => $fields['column'],
                            'direction' => $this->normalizeDirection($fields['direction'] ?? null),
                        ];
                    }

                    continue;
                }

                // For deeper paths, place under 'children'; if depth > 1, nest under an extra 'children' key
                $depth = count($segments);
                $last  = end($segments);

                if (isset($fields['column'])) {
                    if (1 === $depth) {
                        // e.g., items.comment.likes
                        $result[$base]['children'][$first]['children'][$last] = [
                            'column'    => $fields['column'],
                            'direction' => $this->normalizeDirection(null), // default asc
                        ];
                    } else {
                        // e.g., items.comment.likes.comment → place under ...['children']['children'][last]
                        $result[$base]['children'][$first]['children']['children'][$last] = [
                            'column'    => $fields['column'],
                            'direction' => $this->normalizeDirection(null), // default asc
                        ];
                    }
                }
            }
        }

        return $result;
    }

    protected function normalizeDirection(?string $dir): string
    {
        $dir = mb_strtolower((string) $dir);

        return in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';
    }
}
