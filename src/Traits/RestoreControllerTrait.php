<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait RestoreControllerTrait
{
    public function restore(int $id): JsonResponse
    {
        $this->getService()->restore($id);

        return $this->okResponse(
            message: __('messages.successfully.restore_with_id', ['id' => $id]),
        );
    }
}
