<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Controllers;

use Illuminate\Routing\Controller;
use QuantumTecnology\ControllerBasicsExtension\Contracts\ControllerInterface;
use QuantumTecnology\ControllerBasicsExtension\resources\DefaultResource;
use QuantumTecnology\ControllerBasicsExtension\Services\DefaultService;
use QuantumTecnology\ControllerBasicsExtension\Traits\BootControllerTrait;
use QuantumTecnology\ControllerBasicsExtension\Traits\DestroyControllerTrait;
use QuantumTecnology\ControllerBasicsExtension\Traits\IndexControllerTrait;
use QuantumTecnology\ControllerBasicsExtension\Traits\RestoreControllerTrait;
use QuantumTecnology\ControllerBasicsExtension\Traits\ShowControllerTrait;
use QuantumTecnology\ControllerBasicsExtension\Traits\StoreControllerTrait;
use QuantumTecnology\ControllerBasicsExtension\Traits\UpdateControllerTrait;

/**
 * This class is used to create a controller with all the basic CRUD methods.
 * Use this class for example when you want to create a controller for a resource.
 */
abstract class BaseController extends Controller implements ControllerInterface
{
    use BootControllerTrait;
    use DestroyControllerTrait;
    use IndexControllerTrait;
    use RestoreControllerTrait;
    use ShowControllerTrait;
    use StoreControllerTrait;
    use UpdateControllerTrait;

    /*
     * Example.
     *
     * protected string $service  = DefaultService::class;
     * protected string $resource = DefaultResource::class;
     *
     * OR
     *
     * protected function setService(): DefaultService
     * {
     * return app(DefaultService::class);
     * }
     *
     * protected function setResource(): string
     * {
     * return DefaultResource::class;
     * }
     */
}
