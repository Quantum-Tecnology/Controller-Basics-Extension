<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface StoreServiceInterface
{
    public function store(array $data): Model;
}
