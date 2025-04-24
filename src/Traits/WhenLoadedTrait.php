<?php

declare(strict_types = 1);

namespace App\Traits;

trait WhenLoadedTrait
{
    protected function whenLoaded($relationship, $value = null, $default = null): mixed
    {
        return $this->when(
            collect(explode(',', request()->input('include', '')))
                ->contains(fn ($item) => str_contains($relationship, $item)),
            parent::whenLoaded($relationship, $value, $default)
        );
    }
}
