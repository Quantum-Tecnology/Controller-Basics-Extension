<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services\Interfaces;

interface IndexServiceInterface
{
    public function index(array | string | null $fields = [], ?string $search = null, ?array $options = []);
}
