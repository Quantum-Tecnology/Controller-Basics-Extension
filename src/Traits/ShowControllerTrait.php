<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait ShowControllerTrait
{
    public function show(int $id): JsonResponse
    {
        $this->checkIncludes();

        $resource = $this->getResource();

        $result = $this->getService()->show($id);

        return $this->okResponse(
            data: new $resource($result),
            allowedInclude: true,
        );
    }
}
