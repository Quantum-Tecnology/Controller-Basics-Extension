<?php

declare(strict_types=1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

/**
 * TODO: Provavelmente esta trait vai ser reutilizada para outra finalidade, que é fazer o bind de atributos.
 */
trait BindAttributesTrait
{
    public static function bootBindAttributesTrait(): void
    {
        if (!config('bind.enabled')) {
            return;
        }

        static::retrieved(function ($model) {
            collect($model->getAttributes())->each(function ($value, $key) use ($model) {
                if (($attribute = config("bind.attributes.{$key}")) !== null) {
                    $model->setAttribute($attribute, $value);
                }
            });
        });
    }
}
