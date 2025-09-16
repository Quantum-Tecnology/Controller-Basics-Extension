<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Services\GraphQlService;
use QuantumTecnology\ControllerBasicsExtension\Services\RelationshipService;
use QuantumTecnology\ControllerBasicsExtension\Support\FieldSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;
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

        if (!app()->isProduction() && filled($this->allowedFields())) {
            $response->put('allowed_fields', $this->allowedFields());
        }

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
        $keyName     = $this->model()->getKeyName();

        return $this->getBuilderQuery()->execute(
            $this->model(),
            $this->getFields(),
            [
                "({$keyName})" => $idFromParam,
            ] + $this->filterRouteParams($routeParams),
        );
    }

    protected function getFilters(): array
    {
        $queries = request()->query();
        $params  = request()->route()?->parameters();

        $response = array_filter(
            $queries,
            fn ($key): int | false => preg_match('/^filter_?(\w*)?\(([^,()]+)(?:,([^\)]+))?\)$/', (string) $key),
            ARRAY_FILTER_USE_KEY
        );

        $response = array_combine(
            array_map(fn (int | string $key): string => mb_substr((string) $key, 6), array_keys($response)),
            array_values($response)
        );

        return array_merge($response, $this->filterRouteParams($params));
    }

    protected function filterRouteParams(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => ['(' . $key . ')' => $value])
            ->all();
    }

    protected function getFields(): array
    {
        $queries   = request()->query();
        $requested = app(FieldSupport::class)->parse($queries['fields'] ?? '');

        $allowed = $this->allowedFields() ?? [];
        $denied  = [];

        if ($allowed) {
            $requested = $this->filterRequestedFields($requested, $allowed, $denied);
        }

        foreach ($denied as $field) {
            LogSupport::add("Field '{$field}' is not allowed in the request.");
        }

        return $requested;
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

    protected function allowedFields(): array
    {
        return [];
    }

    protected function execute(Model $model, array $data)
    {
        return DB::transaction(fn () => app(RelationshipService::class)->execute($model, $data));
    }

    protected function filterRequestedFields(array $requested, array $allowed, array &$denied = [], string $path = ''): array
    {
        $result = [];

        foreach ($requested as $key => $value) {
            if (is_int($key)) {
                if (in_array($value, $allowed, true)) {
                    $result[] = $value;
                } else {
                    $denied[] = mb_ltrim($path . '.' . $value, '.');
                }
            } elseif (isset($allowed[$key]) && is_array($allowed[$key]) && is_array($value)) {
                $result[$key] = $this->filterRequestedFields($value, $allowed[$key], $denied, mb_ltrim($path . '.' . $key, '.'));
            } else {
                $denied[] = mb_ltrim($path . '.' . $key, '.');
            }
        }

        return $result;
    }
}
