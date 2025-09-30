<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services\Interfaces;

interface DeleteServiceInterface
{
    public function delete($model): bool;
}
