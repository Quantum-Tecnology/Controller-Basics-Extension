<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Request;

class Pagination
{
    protected array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function add(string $field, int $limit, int $perPage): void
    {
        $this->data[] = (object) [
            'field'    => $field,
            'limit'    => $limit,
            'per_page' => $perPage,
        ];
    }
}
