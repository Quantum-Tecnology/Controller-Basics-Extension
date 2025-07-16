<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Presenters;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;
use QuantumTecnology\ModelBasicsExtension\HasManySyncable;
use UnitEnum;

final readonly class GraphQLPresenter
{
    public function __construct(
        private PaginationSupport $paginationSupport,
    ) {
    }

    public function execute(Model $model, array $fields, array $pagination = []): array
    {
        return $this->generate($model, $fields, $pagination);
    }

    private function generate(Model $model, array $fields, array $pagination = [], ?string $relationFullName = null): array
    {
        $actions         = [];
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
                $valueField instanceof UnitEnum          => [
                    'key'   => $valueField->name,
                    'value' => property_exists($valueField, 'value')
                        ? $valueField->value
                        : null,
                    'label' => method_exists($valueField, 'label') ? $valueField->label() : null,
                ],
                default => $valueField
            };

            $existAttribute = false;

            if (str_starts_with((string) $field, 'can_')) {
                $actions[$field] = $valueField;
                $existAttribute  = true;
            } else {
                $data[$field] = $valueField;
            }

            if (!in_array($field, $attributesModel, true) && !$existAttribute) {
                LogSupport::add(__("The field ':field' does not exist in the model ':model'. Please check your fields.", [
                    'field' => $field,
                    'model' => $model->getTable(),
                ]));

                unset($data[$field]);
            }
        }

        $response = [];

        if ([] !== $data) {
            $response = ['data' => $data];
        }

        if ([] !== $actions) {
            $response['actions'] = $actions;
        }

        foreach (array_keys($relations) as $key) {
            $keyCamel = Str::camel($key);

            if (in_array($model->{$keyCamel}()::class, [Relations\BelongsTo::class, Relations\HasOne::class])) {
                $response['data'][$key] = $this->generate($model->{$keyCamel}, $fields[$key]);
            }

            if (in_array($model->{$keyCamel}()::class, [
                Relations\HasMany::class,
                HasManySyncable::class,
                Relations\BelongsToMany::class,
            ])) {
                foreach ($model->{$keyCamel} as $value) {
                    $response['data'][$key]['data'][] = $this->generate(
                        $value,
                        $fields[$key],
                        $pagination,
                        null !== $relationFullName && '' !== $relationFullName && '0' !== $relationFullName ? $key . '.' . $relationFullName . '.' : "{$key}."
                    );
                }

                $paginateName = str_replace('.', '_', $relationFullName . $key);

                $response['data'][$key]['meta'] = $this->generatePagination(
                    $model,
                    $key,
                    $pagination[$paginateName] ?? [],
                    $relationFullName
                );
            }
        }

        return $response;
    }

    private function generatePagination(
        Model $model,
        string $relation,
        array $pagination,
        ?string $fullRelationName = null
    ): array {
        $relationCamel = Str::camel($relation);

        $total = $model->{$relationCamel}->count();
        $limit = $this->paginationSupport->calculatePerPage($pagination['per_page'] ?? null, $relation);

        $pageNameRelation = Str::snake(str_replace('.', '_', $fullRelationName . '.' . $relation));
        $pageName         = preg_replace('/_+/', '_', 'page_' . $pageNameRelation);

        if (($totalRelation = $model->{"{$relation}_count"} ?? 0) > 0) {

            $paginator = new LengthAwarePaginator(
                [],
                $totalRelation,
                $limit,
                $pagination['page'] ?? 1,
                [
                    'path'     => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );

            return [
                'total'          => $paginator->total(),
                'per_page'       => $paginator->perPage(),
                'current_page'   => $paginator->currentPage(),
                'last_page'      => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'page_name'      => $paginator->getOptions()['pageName'],
            ];
        }

        return [
            'total'          => $total,
            'per_page'       => $limit,
            'current_page'   => 1,
            'last_page'      => 1,
            'has_more_pages' => false,
            'page_name'      => $pageName,
        ];
    }

    private function getAllModelAttributes(Model $model): array
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
