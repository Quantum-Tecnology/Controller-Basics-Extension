<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface UpdateServiceInterface
{
    public function update(Model $model, array $data): Model;
}
