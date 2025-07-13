<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Presenters;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

class GraphQLPresenter
{
    public function execute(Model $model, array $fields, array $pagination = []): array
    {
        return $this->generate($model, $fields, $pagination);
    }

    protected function generate(Model $model, array $fields, array $pagination = []): array
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

            if (str_starts_with((string) $field, 'can_')) {
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

        $response = [];

        if ([] !== $data) {
            $response = ['data' => $data];
        }

        if ([] !== $meta) {
            $response['meta'] = $meta;
        }

        foreach (array_keys($relations) as $key) {
            if (in_array($model->{$key}()::class, [Relations\BelongsTo::class, Relations\HasOne::class])) {
                $response['data'][$key] = $this->generate($model->{$key}, $fields[$key], $pagination[$key] ?? []);
            }

            if (in_array($model->{$key}()::class, [Relations\HasMany::class, Relations\BelongsToMany::class])) {
                foreach ($model->{$key} as $value) {
                    $response['data'][$key]['data'][] = $this->generate($value, $fields[$key], $pagination[$key] ?? []);
                    $response['data'][$key]['meta']   = $this->generatePagination($model->{$key});
                }
            }
        }

        return $response;
    }

    protected function generatePagination(Collection $model): array
    {
        return [
            'total' => $model->count(),
        ];
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
