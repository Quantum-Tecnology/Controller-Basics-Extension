<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait StoreControllerTrait
{
    public function store(): JsonResponse
    {
        $this->checkIncludes();

        $resource = $this->getResource();

        return $this->okResponse(
            message: __('messages.successfully.created'),
            data: new $resource($this->getService()->store()),
            allowedInclude: true,
        );
    }
}
