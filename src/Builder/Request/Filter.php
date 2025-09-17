<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Request;

use QuantumTecnology\ControllerBasicsExtension\Builder\Enum\FilterOperation;

class Filter
{
    protected array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function add(string $field, FilterOperation $operation = FilterOperation::Equal): void
    {
        $this->data[] = (object) [
            'field'     => $field,
            'operation' => $operation,
        ];
    }
}
