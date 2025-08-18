<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use QuantumTecnology\ControllerBasicsExtension\resources\DefaultResource;
use QuantumTecnology\ControllerBasicsExtension\Services\DefaultService;
use QuantumTecnology\HandlerBasicsExtension\Traits\ApiResponseTrait;
use QuantumTecnology\ServiceBasicsExtension\Contracts\ServiceInterface;

trait BootControllerTrait
{
    use ApiResponseTrait;
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    private string | ServiceInterface $defaultService = DefaultService::class;
    private string $defaultResource                   = DefaultResource::class;

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

        if(method_exists($this->getService(), 'setAllowedFilters')) {
            $this->getService()->setAllowedFilters($this->allowedFilters);
        }
    }

    public function setService(): ServiceInterface
    {
        return app($this->defaultService);
    }

    public function getService(): ServiceInterface | string
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
