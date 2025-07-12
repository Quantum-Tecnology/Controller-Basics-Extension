<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GenericPresenter;

final class GenericResource extends JsonResource
{
    public function toArray($request): array
    {
        return app(GenericPresenter::class)->transform(
            $this->resource,
            options: $request->all(),
            fields: $request->input('fields', '')
        );
    }
}
