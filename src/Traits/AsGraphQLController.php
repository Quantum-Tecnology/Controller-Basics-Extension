<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Services\GraphQlService;
use QuantumTecnology\ControllerBasicsExtension\Support\FieldSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\FilterSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

trait AsGraphQLController
{
    abstract protected function model(): Model;

    public function index(): JsonResponse
    {
        $response = $this->getGraphQlService()->paginate(
            $this->model(),
            $this->getFields(),
            $this->getFilters(),
            $this->getPagination(),
        );

        return response()->json($response);
    }

    public function show(): JsonResponse
    {
        $response = $this->getGraphQlService()->presenter(
            $this->findBy()->sole(),
            $this->getFields(),
            $this->getPagination(),
        );

        return response()->json($response);
    }

    public function store(): JsonResponse
    {
        $model      = $this->model();
        $dataValues = $this->getDataRequest('store');

        $model = $this->execute($model, $dataValues);

        $response = $this->getGraphQlService()->presenter(
            $model,
            $this->getFields(),
            $this->getPagination(),
        );

        return response()->json($response, Response::HTTP_CREATED);
    }

    public function update(): JsonResponse
    {
        $dataValues = $this->getDataRequest('update');

        $model = $this->findBy()->sole();

        $model = $this->execute($model, $dataValues);

        $response = $this->getGraphQlService()->presenter(
            $model,
            $this->getFields(),
            $this->getPagination(),
        );

        return response()->json($response);
    }

    public function destroy(): Response
    {
        $this->getDataRequest('destroy', true);

        $model = $this->findBy()->sole();

        DB::transaction(function () use ($model) {
            $model->delete();

            return $model;
        });

        return response()->noContent();
    }

    protected function findBy(): Builder
    {
        $routeParams = request()->route()?->parameters() ?: [];
        $idFromParam = array_pop($routeParams);
        $queries     = $this->getFilters();
        $keyName     = $this->model()->getKeyName();

        array_pop($queries['[__model__]']);
        $queries['[__model__]'][$keyName] = ['=' => [$idFromParam]];

        return $this->getBuilderQuery()->execute(
            $this->model(),
            $this->getFields(),
            $queries,
        );
    }

    protected function getFilters(): array
    {
        $queries = request()->query();
        $params  = request()->route()?->parameters();

        return app(FilterSupport::class)->parse($queries + $this->filterRouteParams($params));
    }

    protected function filterRouteParams(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => ['filter(' . $key . ')' => $value])
            ->all();
    }

    protected function getFields(): array
    {
        $queries = request()->query();

        return app(FieldSupport::class)->parse($queries['fields'] ?? '');
    }

    protected function getPagination(): array
    {
        $queries = request()->query();

        return app(PaginationSupport::class)->parse($queries);
    }

    protected function getGraphQlService(): GraphQlService
    {
        return app(GraphQlService::class);
    }

    protected function getBuilderQuery(): BuilderQuery
    {
        return app(BuilderQuery::class);
    }

    /** @codeCoverageIgnore */
    protected function getDataRequest(?string $action = null, bool $exact = false): array
    {
        $class = static::class;
        $parts = explode('Controller', $class);

        if (count($parts) > 2) {
            $last        = array_pop($parts);
            $penultimate = array_pop($parts);
            $class       = implode('Controller', $parts) . 'Request' . $penultimate . 'Request' . $last;
        } else {
            $class = str_replace('Controller', 'Request', $class);
        }
        $class = str_replace('\\Controller\\', '\\Request\\', $class);

        $value = str_replace(['App\\Http\\Controllers\\'],
            ['App\\Http\\Requests\\'],
            static::class);

        if (blank($action)) {
            $request = app($class);
            $request->validated();

            return $request->validated();
        }

        $value = mb_substr($value, 0, -7) . '\\' . ucfirst($action) . 'Request';

        if (class_exists($value)) {
            $request = app($value);
            $request->validated();

            return $request->validated();
        }

        if ($exact) {
            return [];
        }

        return self::getDataRequest();
    }

    protected function execute(Model $model, array $data)
    {
        return DB::transaction(fn () => $this->saveModel($model, $data));
    }

    private function saveModel(Model $model, array $dataValues): Model
    {
        $dataChildren = [];
        $dataFather   = [];

        foreach ($dataValues as $key => $value) {
            $keyCamel = Str::camel($key);

            if (
                is_array($value)
                && method_exists($model, $keyCamel)
                && $model->{$keyCamel}() instanceof Relation
            ) {
                if (in_array($model->{$keyCamel}()::class, [
                    Relations\HasOne::class,
                    Relations\BelongsTo::class,
                ], true)) {
                    $dataFather[$key] = [
                        'model' => $model->{$keyCamel}()->getRelated(),
                        'value' => $value,
                        'key'   => $model->{$keyCamel}()->getForeignKeyName(),
                    ];
                } else {
                    $dataChildren[$key] = $value;
                }
                unset($dataValues[$key]);
            }
        }

        foreach ($dataFather as $value) {
            $dataValues[$value['key']] = $this->saveModel(new $value['model'](), $value['value']);
        }

        $model->fill($dataValues);
        $model->save();

        foreach ($dataChildren as $key => $value) {
            $keyCamel       = Str::camel($key);
            $typeRelation   = $model->{$keyCamel}();
            $classRelated   = $model->{$keyCamel}()->getRelated();
            $idDataChildren = [];

            foreach ($value as $value2) {
                $dataArray = [];

                foreach ($value2 as $key3 => $value3) {
                    $key3Camel = Str::camel($key3);

                    if (
                        is_array($value3)
                        && method_exists($classRelated, $key3Camel)
                        && $classRelated->{$key3Camel}() instanceof Relation
                    ) {
                        $dataArray[$key3] = $value3;
                        unset($value2[$key3]);
                    }
                }

                if ($typeRelation instanceof Relations\HasMany) {
                    $modelInternal = $model->{$keyCamel}();
                    $idModel       = $modelInternal->getRelated()->getKeyName();

                    if (array_key_exists($idModel, $value2)) {
                        $newModel = $typeRelation->where($idModel, $value2[$idModel])->sole();
                        $newModel->update($value2);
                    } else {
                        $newModel = $modelInternal->create($value2);
                    }
                    $this->saveModel($newModel, $dataArray);
                }

                if ($typeRelation instanceof Relations\BelongsToMany) {
                    ksort($value2);

                    $name = json_encode($value2, JSON_THROW_ON_ERROR);

                    if (!isset($idDataChildren[$name])) {
                        $idDataChildren[$name] = $classRelated->create($value2);
                    }
                }
            }

            if (filled($idDataChildren)) {
                $typeRelation->attach($idDataChildren);
            }
        }

        return $model;
    }
}
