<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Presenters;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;
use ReflectionClass;

class GraphQLPresenter
{
    public function execute(Model $model, array $fields, array $pagination = []): array
    {
        $data = [];
        $meta = [];

        $attributesModel = $this->getAllModelAttributes($model);

        foreach ($fields as $key => $all) {
            if ('*' === $all) {
                unset($fields[$key]);
                $fields = array_merge($fields, $attributesModel);

                break;
            }
        }

        foreach ($fields as $field) {
            $valueField = $model->{$field};

            $valueField = match (true) {
                $valueField instanceof DateTimeInterface => $valueField->toDateTimeString(),
                default                                  => $valueField
            };

            if (str_starts_with($field, 'can_')) {
                $meta[$field] = $valueField;
            } else {
                $data[$field] = $valueField;
            }

            if (!in_array($field, $attributesModel, true)) {
                LogSupport::add(__("The field ':field' does not exist in the model ':model'. Please check your fields.", [
                    'field' => $field,
                    'model' => $model->getTable(),
                ]));
            }
        }

        $response = compact('data');

        if ($meta) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    protected function getAllModelAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();

        foreach ((new ReflectionClass($model))->getMethods() as $method) {
            if (preg_match('/^get(.+)Attribute$/', $method->name, $matches)) {
                $key              = Str::snake($matches[1]);
                $attributes[$key] = $model->{$key};
            }

            if (0 === $method->getNumberOfParameters()) {
                $returnType = $method->getReturnType();

                if ($returnType && \Illuminate\Database\Eloquent\Casts\Attribute::class === $returnType->getName()) {
                    $key              = Str::snake($method->name);
                    $attribute        = $model->{$key};
                    $attributes[$key] = $attribute;
                }
            }
        }

        foreach ($model->getMutatedAttributes() as $key) {
            if (!array_key_exists($key, $attributes)) {
                $attributes[$key] = $model->{$key};
            }
        }

        return array_keys($attributes);
    }
}
