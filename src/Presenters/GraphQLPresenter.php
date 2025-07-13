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

class GraphQLPresenter
{
    public function __construct(
        protected PaginationSupport $paginationSupport,
    ) {
    }

    public function execute(Model $model, array $fields, array $pagination = []): array
    {
        return $this->generate($model, $fields, $pagination);
    }

    protected function generate(Model $model, array $fields, array $pagination = [], ?string $relationFullName = null): array
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
                    $response['data'][$key]['data'][] = $this->generate(
                        $value, $fields[$key],
                        $pagination[$key] ?? [],
                        $relationFullName . '.'
                    );
                    $response['data'][$key]['meta'] = $this->generatePagination(
                        $model, $key,
                        $pagination[$key] ?? [],
                        $relationFullName
                    );
                }
            }
        }

        return $response;
    }

    protected function generatePagination(
        Model $model,
        string $relation,
        array $pagination,
        ?string $fullRelationName = null
    ): array {
        $total = $model->{$relation}->count();

        if (($totalRelation = $model->{"{$relation}_count"}) > 0) {
            $pageName = Str::camel(str_replace('.', '_', $fullRelationName . '.' . $relation));

            $paginator = new LengthAwarePaginator(
                [],
                $totalRelation,
                $this->paginationSupport->calculatePerPage($pagination['per_page'] ?? null, $relation),
                $pagination['page'] ?? 1,
                [
                    'path'     => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => 'page_' . $pageName,
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
            'total' => $total,
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
