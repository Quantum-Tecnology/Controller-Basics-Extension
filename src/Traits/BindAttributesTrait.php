<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

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
        collect($this->getAttributes())->each(function ($value, $key) {
            if (($attribute = config("bind.attributes.{$key}")) !== null && !in_array($attribute, $this->exceptBindFields())) {
                $this->setAttribute($attribute, $value);
            }
        });
    }

    protected function removeBindAttributes(): void
    {
        foreach (config('bind.attributes') as $key => $value) {
            if (in_array($value, array_keys($this->getAttributes())) && !in_array($key, $this->exceptBindFields())) {
                $this->setAttribute($key, $this->getAttribute($value));
                $this->{$key} = $this->getAttribute($value);
                unset($this->{$value});
            }
        }
    }

    protected function exceptBindFields(): array {
        return [];
    }

}
