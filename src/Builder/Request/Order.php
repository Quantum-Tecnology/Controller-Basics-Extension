<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Request;

use QuantumTecnology\ControllerBasicsExtension\Builder\Enum\OrderDirection;

class Order
{
    protected array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function add(string $field, string $column, OrderDirection $direction = OrderDirection::Asc): void
    {
        $this->data[] = (object) [
            'field'     => $field,
            'column'    => $column,
            'direction' => $direction,
        ];
    }
}
