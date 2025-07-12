<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use QuantumTecnology\ControllerBasicsExtension\QueryBuilder\GenerateQuery;
use QuantumTecnology\ControllerBasicsExtension\Resources\GenericResource;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginateSupport;

trait AsApiController
{
    abstract protected function model(): Model;

    public function index(Request $request, PaginateSupport $paginateSupport): AnonymousResourceCollection
    {
        $query = $this->queryModel($request, __FUNCTION__);

        $page    = $request->input('page', 1);
        $perPage = $paginateSupport->calculatePerPage($request->input('per_page'), 'father');

        $models = $query->paginate($perPage, ['*'], 'page', $page);

        return GenericResource::collection($models);
    }

    public function store(): GenericResource
    {
        $request = app($this->getNamespaceRequest('store'));

        abort_unless($request->authorize(), 403, 'This action is unauthorized.');

        return DB::transaction(function () use ($request): GenericResource {
            $data       = $request->validated();
            $modelClass = $this->model();

            $dataArray = [];

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $dataArray[$key] = $value;
                    unset($data[$key]);
                }
            }

            $model = $modelClass->create($data);
            $this->saveStoreChildren($model, $dataArray);

            return new GenericResource($this->queryModel(request(), 'store')->find($model->id));
        });
    }

    public function show(Request $request): GenericResource
    {
        return new GenericResource($this->findByOne($request));
    }

    public function update(): GenericResource
    {
        $request = app($this->getNamespaceRequest('update'));
        $model   = $this->findByOne($request);

        abort_unless($request->authorize($model), 403, 'This action is unauthorized.');

        return new GenericResource(tap($model)->update($request->validated()));
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->findByOne($request)->delete();

        return response()->json();
    }

    protected function findByOne(Request $request): Model
    {
        $routeParams = $request->route()?->parameters() ?: [];

        $id = $this->model()->getKeyName();

        return $this->queryModel($request, __FUNCTION__)->where($id, end($routeParams))->sole();
    }

    protected function queryModel(Request $request, string $action): Builder
    {
        $data = $request->query();

        $query = app(GenerateQuery::class, [
            'model'         => $this->model(),
            'classCallable' => $this,
            'action'        => $action,
        ])->execute(
            fields: $data['fields'] ?? '',
            pagination: app(PaginateSupport::class)->extractPagination($data),
            filters: $this->extractFilter($data),
        );

        if (config('app.debug')) {
            match (true) {
                $request->has('dd')       => $query->dd(),
                $request->has('dump')     => $query->dump(),
                $request->has('dd_raw')   => $query->ddRawSql(),
                $request->has('dump_raw') => $query->dumpRawSql(),
                default                   => false,
            };
        }

        return $query;
    }

    protected function getNamespaceRequest(?string $action = null): string
    {
        $value = str_replace(['Controller', 'App\\Http\\Controllers\\'],
            ['Request', 'App\\Http\\Requests\\'],
            static::class);

        if (blank($action)) {
            return $value;
        }

        $value = mb_substr($value, 0, -7) . '\\' . ucfirst($action) . 'Request';

        if (class_exists($value)) {
            return $value;
        }

        return self::getNamespaceRequest();
    }

    protected function extractFilter(array $input): array
    {
        $filters = [];

        foreach ($input as $key => $value) {
            if (preg_match('/^filter_([^\(]+)\(([^\),]+)(?:,([^\)]+))?\)$/', $key, $matches)) {
                $relationPath = $matches[1];
                $field        = $matches[2];
                $operator     = $matches[3] ?? '=';

                // Split values by comma or pipe
                if (is_string($value)) {
                    $parsedValues = preg_split('/[,\|]/', $value);
                } elseif (is_array($value)) {
                    $parsedValues = $value;
                } else {
                    $parsedValues = [$value];
                }

                // Normalize values
                $parsedValues = array_map(static function ($v): int | string {
                    $v = mb_trim((string) $v);

                    return is_numeric($v) ? (int) $v : $v;
                }, $parsedValues);

                $filters[$relationPath][$field][$operator] = $parsedValues;
            }
        }

        return $this->cleanFilters($filters);
    }

    /**
     * filter_comments_comments_data(post_id,>=) = 1,2
     * filter_comments(id) = 3,4
     * filter(id) = 10,20
     * filter_comments_comments_data(post_id,<=) = 3,4.
     *
     * devo retornar assim para mim
     * [
     * "comments_comments_data" => ["post_id" => ["<=" => [1, 2], ">=" => [3, 4]]],
     * "comments" => ["id" => ["=" => [1]]]
     * "__default__" => ["id" => ["=" => [10,20]]]
     * ]
     */
    protected function cleanFilters(array $filters): array
    {
        foreach ($filters as $relation => &$fields) {
            foreach ($fields as $field => &$operators) {
                foreach ($operators as $operator => &$values) {
                    // Remove empty/null/blank values from the values array
                    $values = array_filter($values, fn ($v): bool => !(null === $v || '' === $v || [] === $v));

                    if ([] === $values) {
                        unset($operators[$operator]);
                    } else {
                        $operators[$operator] = array_values($values); // reindex
                    }
                }
                unset($values);

                if (empty($operators)) {
                    unset($fields[$field]);
                }
            }
            unset($operators);

            if (empty($fields)) {
                unset($filters[$relation]);
            }
        }
        unset($fields);

        return $filters;
    }

    protected function saveStoreChildren(Model $model, array $children): void
    {
        foreach ($children as $key => $value) {
            $ids = [];

            foreach ($value as $value2) {
                $dataArray = [];

                foreach ($value2 as $key3 => $value3) {
                    if (is_array($value3)) {
                        $dataArray[$key3] = $value3;
                        unset($value2[$key3]);
                    }
                }

                if ($model->{$key}() instanceof Relations\HasMany) {
                    $newModel = $model->{$key}()->create($value2);
                }

                if ($model->{$key}() instanceof Relations\BelongsToMany) {
                    $belongsToMany = $model->{$key}()->getRelated();
                    ksort($value2);

                    if (!isset($ids[json_encode($value2)])) {
                        $ids[json_encode($value2)] = $belongsToMany->create($value2);
                    }
                }

                if (isset($newModel) && filled($dataArray)) {
                    $this->saveStoreChildren($newModel, $dataArray);
                }
            }

            if (filled($ids)) {
                $model->{$key}()->attach($ids);
            }
        }
    }
}
