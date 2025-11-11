<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ByFilterTrait
{
    #[Scope]
    public function byFilter(Builder $builder, Collection $fields, Collection $values): void
    {
        $builder->where(function ($builder) use ($fields, $values): void {
            foreach ($values as $value) {
                foreach ($fields as $field) {
                    $builder->orWhereLike($field, '%' . $value . '%');
                }
            }
        });
    }
}
