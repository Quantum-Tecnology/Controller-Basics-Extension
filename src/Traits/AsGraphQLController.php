<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Services\GraphQlService;
use QuantumTecnology\ControllerBasicsExtension\Support\FieldSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\FilterSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

trait AsGraphQLController
{
    abstract protected function model(): Model;

    public function index(
        Request $request,
        GraphQlService $graphQlService,
        FieldSupport $fieldSupport,
        FilterSupport $filterSupport,
        PaginationSupport $paginationSupport,
    ): JsonResponse {
        $queries  = $request->query();
        $params   = $request->route()?->parameters() ?: [];
        $filters  = $filterSupport->parse($queries + $this->filterRouteParams($params));
        $response = $graphQlService->paginate(
            $this->model(),
            $fieldSupport->parse($queries['fields'] ?? ''),
            $filters,
            $paginationSupport->parse($queries),
        );

        return response()->json($response);
    }

    public function show(
        Request $request,
        BuilderQuery $builderQuery,
        FieldSupport $fieldSupport,
        FilterSupport $filterSupport,
        GraphQLPresenter $presenter,
    ): JsonResponse {
        [$queries, $filters, $model] = $this->findBySole($request, $filterSupport, $fieldSupport, $builderQuery);

        $response = $presenter->execute($model, $fieldSupport->parse($queries['fields'] ?? ''), $filters);

        return response()->json($response, Response::HTTP_OK);
    }

    public function destroy(
        Request $request,
        BuilderQuery $builderQuery,
        FilterSupport $filterSupport,
        FieldSupport $fieldSupport,
    ): Response {
        [, , $model] = $this->findBySole($request, $filterSupport, $fieldSupport, $builderQuery);

        $model->delete();

        return response()->noContent();
    }

    public function store(
        Request $request,
        GraphQLPresenter $presenter,
        FieldSupport $fieldSupport,
    ): JsonResponse {
        $requestValid = app($this->getNamespaceRequest('store'));

        abort_unless($requestValid->authorize(), 403, 'This action is unauthorized.');

        $dataArray  = [];
        $dataValues = $requestValid->validated();

        foreach ($dataValues as $key => $value) {
            if (is_array($value)) {
                $dataArray[$key] = $value;
                unset($dataValues[$key]);
            }
        }

        $model = DB::transaction(function () use ($dataValues, $dataArray) {
            $model = $this->model()->create($dataValues);
            $this->saveStoreChildren($model, $dataArray);

            return $model;
        });

        $response = $presenter->execute(
            $model,
            $fieldSupport->parse($request->query()['fields'] ?? '')
        );

        return response()->json($response, Response::HTTP_CREATED);
    }

    public function update(
        BuilderQuery $builderQuery,
        FieldSupport $fieldSupport,
        FilterSupport $filterSupport,
        GraphQLPresenter $presenter,
    ): JsonResponse {
        $request = app($this->getNamespaceRequest('update'));
        abort_unless($request->authorize(), 403, 'This action is unauthorized.');

        [$queries, $filters, $model] = $this->findBySole($request, $filterSupport, $fieldSupport, $builderQuery);
        $model->update($request->validated());

        $response = $presenter->execute($model, $fieldSupport->parse($queries['fields'] ?? ''), $filters);

        return response()->json($response, Response::HTTP_OK);
    }

    public function findBySole(
        mixed $request,
        FilterSupport $filterSupport,
        FieldSupport $fieldSupport,
        BuilderQuery $builderQuery
    ): array {
        $p       = $request->route()?->parameters() ?: [];
        $key     = $this->model()->getKeyName();
        $id      = array_pop($p);
        $queries = ["filter({$key})" => $id] + $request->query();

        $model = $builderQuery->execute(
            $this->model(),
            $fieldSupport->parse($request->query()['fields'] ?? ''),
            $filters = $filterSupport->parse($queries)
        )->sole();

        return [$queries, $filters, $model];
    }

    protected function filterRouteParams(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => ['filter(' . $key . ')' => $value])
            ->all();
    }

    protected function getNamespaceRequest(?string $action = null): string
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
        // Replace the namespace segment
        $class = str_replace('\\Controller\\', '\\Request\\', $class);

        $value = str_replace(['App\\Http\\Controllers\\'],
            ['App\\Http\\Requests\\'],
            static::class);

        if (blank($action)) {
            return $class;
        }

        $value = mb_substr($value, 0, -7) . '\\' . ucfirst($action) . 'Request';

        if (class_exists($value)) {
            return $value;
        }

        return self::getNamespaceRequest();
    }

    /**
     * @codeCoverageIgnore
     * // TODO: Implement a method to save children models in a GraphQL context.
     */
    protected function saveStoreChildren(Model $model, array $children): void
    {
        foreach ($children as $key => $value) {
            $ids      = [];
            $keyCamel = Str::camel($key);

            foreach ($value as $value2) {
                $dataArray = [];

                foreach ($value2 as $key3 => $value3) {
                    if (is_array($value3)) {
                        $dataArray[$key3] = $value3;
                        unset($value2[$key3]);
                    }
                }

                if ($model->{$keyCamel}() instanceof Relations\HasMany) {
                    $newModel = $model->{$key}()->create($value2);
                }

                if ($model->{$keyCamel}() instanceof Relations\BelongsToMany) {
                    $belongsToMany = $model->{$keyCamel}()->getRelated();
                    ksort($value2);

                    if (!isset($ids[$name = json_encode($value2, JSON_THROW_ON_ERROR)])) {
                        $ids[$name] = $belongsToMany->create($value2);
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
