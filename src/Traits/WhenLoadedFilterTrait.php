<?php

declare(strict_types = 1);

namespace App\Traits;

trait WhenLoadedFilterTrait
{
    protected function whenLoadedFilter($relationship, $filter, $value = null, $default = null): mixed
    {
        $isTrue = request('filter', false) && (request('filter', false)[$filter] ?? false) === 'true' ? $relationship : false;

        return $this->when($isTrue, fn () => parent::whenLoaded($relationship, $value, $default));
    }
}
