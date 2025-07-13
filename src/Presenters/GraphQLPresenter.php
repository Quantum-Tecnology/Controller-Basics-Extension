<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Presenters;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

class GraphQLPresenter
{
    public function execute(Model $model, array $fields, array $pagination = []): array
    {
        return $this->generate($model, $fields, $pagination);
    }

    protected function generate(Model $model, array $fields, array $pagination = [])
    {
        $meta            = [];
        $data            = [];
        $attributesModel = $this->getAllModelAttributes($model);

        foreach ($fields as $key => $all) {
            if ('*' === $all) {
                unset($fields[$key]);
                $fields = array_merge($fields, $attributesModel);

                break;
            }
        }

        $relations = [];

        foreach ($fields as $key => $field) {
            if (is_array($field)) {
                $relations[$key] = $field;

                continue;
            }
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

        foreach ($relations as $key => $relation) {
            if (in_array($model->{$key}()::class, [Relations\BelongsTo::class, Relations\HasOne::class])) {
                $response['data'][$key] = $this->generate($model->{$key}, $fields[$key], $pagination[$key] ?? []);
            }
        }

        return $response;
    }

    protected function getAllModelAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();

        foreach ($model->getMutatedAttributes() as $key) {
            if (!array_key_exists($key, $attributes)) {
                $attributes[$key] = $model->{$key};
            }
        }

        return array_keys($attributes);
    }
}
