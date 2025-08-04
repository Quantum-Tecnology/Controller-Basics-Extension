<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class PaginationSupport
{
    public function parse(array $data): array
    {
        $pagination = [];

        foreach ($data as $key => $value) {
            if ('per_page' === $key || 'page' === $key) {
                $pagination[$key] = (int) $value;

                continue;
            }

            if (preg_match('/^(per_page|page)_(.+)$/', $key, $matches)) {
                [$type, $rawPath] = [$matches[1], $matches[2]];
                $pathParts        = explode('.', $rawPath);

                $ref = &$pagination;

                foreach ($pathParts as $part) {
                    if (!isset($ref[$part])) {
                        $ref[$part] = [];
                    }
                    $ref = &$ref[$part];
                }
                $ref[$type] = (int) $value;
                unset($ref);
            }
        }

        return $pagination;
    }

    public function calculatePerPage(?int $perPage, string $path): int
    {
        if (blank($perPage)) {
            $perPage = config('page.per_page');
        }

        if ($perPage > config('page.max_page')) {
            LogSupport::add(
                __('The :field value (:per_page) exceeds the maximum allowed (:max_page). It has been set to the maximum value of :max_page.', [
                    'per_page' => $perPage,
                    'max_page' => config('page.max_page'),
                    'field'    => 'per_page_' . str_replace('.', '_', $path),
                ])
            );

            $perPage = config('page.max_page');
        }

        return (int) ($perPage ?: 1);
    }
}
