<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DefaultResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
