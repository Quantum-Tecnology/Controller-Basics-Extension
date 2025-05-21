<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\resources;

use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class DefaultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
