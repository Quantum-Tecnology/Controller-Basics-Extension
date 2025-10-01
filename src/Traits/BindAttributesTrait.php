<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

/**
 * // TODO: Está em fase de desenvolvimento, não utilizar ainda. https://github.com/Quantum-Tecnology/Controller-Basics-Extension/issues/13.
 */
trait BindAttributesTrait
{
    public static function bootBindAttributesTrait(): void
    {
        if (!config('bind.enabled')) {
            return;
        }

        static::retrieved(fn ($model) => $model->addBindAttributes());
        static::saving(fn ($model) => $model->removeBindAttributes());
    }

    protected function addBindAttributes(): void
    {
        collect($this->getAttributes())->each(function ($value, $key): void {
            if (($attribute = config("bind.attributes.{$key}")) !== null && !in_array($attribute, $this->exceptBindFields())) {
                $this->setAttribute($attribute, $value);
            }
        });
    }

    protected function removeBindAttributes(): void
    {
        collect($this->getAttributes())
            ->each(function ($value, $key): void {
                if (
                    ($attribute = config("bind.attributes.{$key}")) !== null
                    && !in_array($attribute, $this->exceptBindFields())
                ) {
                    $this->setAttribute($key, $this->getDirty()[$key] ?? $this->getDirty()[$attribute] ?? null);
                    $this->{$key} = $this->getDirty()[$key] ?? $this->getDirty()[$attribute] ?? null;
                    unset($this->{$attribute});
                }

                $f = array_keys(array_filter(config('bind.attributes'), fn ($item) => $item === $key));

                if (filled($f) && !in_array($key, $this->exceptBindFields())) {
                    $field = $f[0];

                    $this->setAttribute($field, $this->getDirty()[$key] ?? $this->getDirty()[$attribute] ?? null);
                    $this->{$field} = $this->getDirty()[$key] ?? $this->getDirty()[$attribute] ?? null;
                    unset($this->{$key});
                }
            });
    }

    protected function exceptBindFields(): array
    {
        return [];
    }
}
