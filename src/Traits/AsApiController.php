<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    final public function index(Request $request, PaginateSupport $paginateSupport): AnonymousResourceCollection
    {
        $query = $this->queryModel($request, __FUNCTION__);

        $page    = $request->input('page', 1);
        $perPage = $paginateSupport->calculatePerPage($request->input('per_page'), 'father');

        $models = $query->paginate($perPage, ['*'], 'page', $page);

        return GenericResource::collection($models);
    }

    final public function store(): GenericResource
    {
        $request = app($this->getNamespaceRequest('store'));

        abort_unless($request->authorize(), 403, 'This action is unauthorized.');

        return DB::transaction(function () use ($request) {
            $data       = $request->validated();
            $modelClass = $this->model();

            $hasMany = [];

            foreach ($data as $key => $value) {
                if (is_array($value) && $modelClass->{$key}() instanceof HasMany) {
                    $hasMany[$key] = $value;
                    unset($data[$key]);
                }
            }

            $model = $modelClass->create($data);
            $this->saveChildren($model, $hasMany);

            return new GenericResource($this->queryModel(request(), 'store')->find($model->id));
        });
    }

    final public function show(Request $request): GenericResource
    {
        return new GenericResource($this->findByOne($request));
    }

    final public function update(): GenericResource
    {
        $request = app($this->getNamespaceRequest('update'));
        $model   = $this->findByOne($request);

        abort_unless($request->authorize($model), 403, 'This action is unauthorized.');

        return new GenericResource(tap($model)->update($request->validated()));
    }

    final public function destroy(Request $request): JsonResponse
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
            fields: $data['fields'] ?? [],
            pagination: app(PaginateSupport::class)->extractPagination($data),
            filters: $this->extractFilter($data),
        );

        if (config('app.debug')) {
            match (true) {
                request()->has('dd')       => $query->dd(),
                request()->has('dump')     => $query->dump(),
                request()->has('dd_raw')   => $query->ddRawSql(),
                request()->has('dump_raw') => $query->dumpRawSql(),
                default                    => false,
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
            if (preg_match('/^filter_(.+?)(?:\[(.+)\])?$/', $key, $matches)) {
                $rawPath  = $matches[1];
                $operator = $matches[2] ?? null;

                $segments     = explode('_', $rawPath);
                $field        = array_pop($segments);
                $isBy         = 'by' === end($segments);
                $relationPath = implode('_', $segments) ?: $this->model()::class;

                if ($isBy) {
                    $field        = 'by_' . $field;
                    $relationPath = mb_substr($relationPath, 0, -3);
                }

                if (is_array($value) && null === $operator) {
                    foreach ($value as $op => $val) {
                        $parsedValues = is_string($val)
                            ? explode('|', $val)
                            : (array) $val;

                        $parsedValues = array_map(function ($v) {
                            $v = mb_trim($v);

                            return is_numeric($v) ? (int) $v : $v;
                        }, $parsedValues);

                        $filters[$relationPath][$field][$op] = $parsedValues;
                    }
                } else {
                    if (is_array($value)) {
                        $parsedValues = $value;
                    } elseif (is_string($value)) {
                        $parsedValues = explode('|', $value);
                    } else {
                        $parsedValues = [$value];
                    }

                    $parsedValues = array_map(function ($v) {
                        $v = mb_trim($v ?: '');

                        return is_numeric($v) ? (int) $v : $v;
                    }, $parsedValues);

                    $filters[$relationPath][$field][$operator ?? 'in'] = $parsedValues;
                }
            }
        }

        return $this->cleanFilters($filters);

    }

    protected function cleanFilters(array $filters): array
    {
        foreach ($filters as $relation => $fields) {
            foreach ($fields as $field => $operators) {
                foreach ($operators as $operator => $values) {
                    if (empty($values) || (is_array($values) && 0 === count(array_filter($values, fn ($v) => null !== $v && '' !== $v && [] !== $v)))) {
                        unset($filters[$relation][$field][$operator]);
                    }
                }

                if (empty($filters[$relation][$field])) {
                    unset($filters[$relation][$field]);
                }
            }

            if (empty($filters[$relation])) {
                unset($filters[$relation]);
            }
        }

        return $filters;
    }

    protected function saveChildren(Model $model, array $children): void
    {
        foreach ($children as $key => $value) {
            foreach ($value as $value2) {
                $hasMany = [];

                foreach ($value2 as $key3 => $value3) {
                    if (is_array($value3)) {
                        $hasMany[$key3] = $value3;
                        unset($value2[$key3]);
                    }
                }

                if ($model->{$key}() instanceof HasMany) {
                    $newModel = $model->{$key}()->create($value2);

                    if (filled($hasMany)) {
                        $this->saveChildren($newModel, $hasMany);
                    }
                }
            }
        }
    }
}
