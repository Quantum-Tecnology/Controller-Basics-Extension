<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

trait FkChangeTrait
{
    public static function bootFkChangeTrait(): void
    {
        static::retrieved(function ($model) {
            collect($model->getAttributes())->each(function ($value, $key) use ($model) {
                $attributes = config('hashids.attributes');

                if (in_array($key, $attributes)) {
                    $model->setAttribute($key, (int) $value);
                }
            });
        });
    }
}
