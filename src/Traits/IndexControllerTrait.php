<?php

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait IndexControllerTrait
{
    public function index(): JsonResponse
    {
        $this->checkIncludes();

        return $this->okResponse(
            data: $this->getResource()::collection($this->getService()->index()),
            allowedInclude: true,
            allowedFilters: true,
        );
    }
}
