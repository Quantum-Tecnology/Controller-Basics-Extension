<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

final class PaginateSupport
{
    public function calculatePerPage(?string $perPage, string $path): int
    {
        if (blank($perPage)) {
            $perPage = config('quantum-controller-graphql.per_page');
        }

        if ($perPage > config('quantum-controller-graphql.max_page')) {
            LogSupport::add(
                __('The :field value (:per_page) exceeds the maximum allowed (:max_page). It has been set to the maximum value of :max_page.', [
                    'per_page' => $perPage,
                    'max_page' => config('quantum-controller-graphql.max_page'),
                    'field'    => 'per_page_' . str_replace('.', '_', $path),
                ])
            );

            $perPage = config('quantum-controller-graphql.max_page');
        }

        return (int) ($perPage ?: 1);
    }

    public function extractPagination(array $input): array
    {
        $pagination = [];

        foreach ($input as $key => $value) {
            if (preg_match('/^(per_page|page)_(.+)$/', $key, $matches)) {
                [$type, $rawPath] = [$matches[1], $matches[2]];

                $relationPath = str_replace('_', '.', $rawPath);

                if (!isset($pagination[$relationPath])) {
                    $pagination[$relationPath] = [];
                }

                $pagination[$relationPath][$type] = (int) $value;
            }
        }

        return $pagination;
    }
}
