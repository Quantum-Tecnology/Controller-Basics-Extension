<?php

declare(strict_types=1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

/**
 * TODO: Provavelmente esta trait vai ser reutilizada para outra finalidade, que é fazer o bind de atributos.
 */
trait FkChangeTrait
{
    public static function bootFkChangeTrait(): void
    {
        static::retrieved(function ($model) {
            collect($model->getAttributes())->each(function ($value, $key) use ($model) {
                $attributes = config('translate.attributes');

                if (array_key_exists($key, $attributes)) {
                    $model->setAttribute($attributes[$key], (int) $value);
                }
            });
        });
    }
}
