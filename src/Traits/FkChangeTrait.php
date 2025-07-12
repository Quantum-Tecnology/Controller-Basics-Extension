<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

/**
 * @deprecated foi substituido pelo BindAttributesTrait
 * @see BindAttributesTrait
 * @since 2.5.8
 */
trait FkChangeTrait
{
    public static function bootFkChangeTrait(): void
    {
        static::retrieved(function ($model): void {
            collect($model->getAttributes())->each(function ($value, $key) use ($model): void {
                $attributes = config('translate.attributes');

                if (array_key_exists($key, $attributes)) {
                    $model->setAttribute($attributes[$key], (int) $value);
                }
            });
        });
    }
}
