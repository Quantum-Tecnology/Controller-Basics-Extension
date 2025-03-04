<?php

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Http\JsonResponse;

trait DestroyControllerTrait
{
    public function destroy(int $id): JsonResponse
    {
        if (!$this->getService()->destroy($id)) {
            return response()->json([
                'message' => "Não foi possivel deletar o registro {$id}!",
            ]);
        }

        return $this->okResponse(
            message: __('messages.successfully.deleted_with_id', ['id' => $id]),
        );
    }
}
