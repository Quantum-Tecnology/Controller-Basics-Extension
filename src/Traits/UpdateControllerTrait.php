<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait UpdateControllerTrait
{
    public function update(int $id): JsonResponse
    {
        $this->checkIncludes();

        $resource = $this->getResource();

        return $this->okResponse(
            message: __('messages.successfully.updated'),
            data: new $resource($this->getService()->update($id)),
            allowedInclude: true,
        );
    }
}
