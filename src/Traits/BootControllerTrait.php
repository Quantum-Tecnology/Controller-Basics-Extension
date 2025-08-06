<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use QuantumTecnology\ControllerBasicsExtension\Resources\DefaultResource;
use QuantumTecnology\ControllerBasicsExtension\Services\DefaultService;
use QuantumTecnology\HandlerBasicsExtension\Traits\ApiResponseTrait;
use QuantumTecnology\ServiceBasicsExtension\BaseService;

trait BootControllerTrait
{
    use ApiResponseTrait;
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    private string | BaseService $defaultService = DefaultService::class;
    private string $defaultResource              = DefaultResource::class;

    public function booted(): void
    {
        if ($this->service ?? false) {
            $this->defaultService = $this->service;
        }

        if (is_string($this->defaultService)) {
            $this->defaultService = $this->setService();
        }

        if ($this->resource ?? false) {
            $this->defaultResource = $this->resource;
        }

        if (is_string($this->defaultResource)) {
            $this->defaultResource = $this->setResource();
        }

        throw_if(
            empty($this->getResource()),
            new Exception('Resource must be defined in the ' . request()->route()->getAction('controller') . '.')
        );

        throw_if(
            empty($this->getService()),
            new Exception('Service must be defined in the ' . request()->route()->getAction('controller') . '.')
        );
    }

    public function setService(): BaseService
    {
        return app($this->defaultService);
    }

    public function getService(): BaseService | string
    {
        return $this->defaultService;
    }

    public function setResource(): string
    {
        return $this->defaultResource;
    }

    public function getResource(): string
    {
        return $this->defaultResource;
    }
}
