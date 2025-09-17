<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Request;

use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

class Pagination
{
    protected array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function add(string $field, int $limit, int $perPage): void
    {
        if ($perPage > config('page.max_page')) {
            LogSupport::add(
                __('The :field value (:per_page) exceeds the maximum allowed (:max_page). It has been set to the maximum value of :max_page.', [
                    'per_page' => $perPage,
                    'max_page' => config('page.max_page'),
                    'field'    => 'per_page_' . str_replace('.', '_', $field),
                ])
            );

            $perPage = config('page.max_page');
        }

        $this->data[] = (object) [
            'field'    => $field,
            'limit'    => $limit,
            'per_page' => $perPage,
        ];
    }
}
