<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait IndexControllerTrait
{
    public function index(): JsonResponse
    {
        $this->checkIncludes();
        $result = $this->getService()->index();

        return $this->okResponse(
            message: $result->message,
            data: $this->getResource()::collection($result->data),
            allowedInclude: true,
            allowedFilters: true,
        );
    }
}
