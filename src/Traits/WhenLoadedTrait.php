<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

trait WhenLoadedTrait
{
    protected function whenLoaded($relationship, $value = null, $default = null): mixed
    {
        $field = 'includes';

        return $this->when(
            request()->input($field) && collect(explode(',', request()->input($field, '')))
                ->contains(fn ($item): bool => str_contains($item, (string) $relationship)),
            parent::whenLoaded($relationship, $value, $default)
        );
    }
}
