<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use QuantumTecnology\ControllerBasicsExtension\Builder\GraphBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

trait AsGraphQLController
{
    abstract protected function model(): Model;

    public function index(QueryBuilder $queryBuilder, GraphBuilder $graphBuilder, Request $request): JsonResponse
    {
        $fields = request()->query('fields');
        $result = $queryBuilder->execute($this->model(), $request->query('fields'), $request->query());

        if ($request->query('order_column')) {
            $result->orderBy($request->query('order_column'), $request->query('order_direction'));
        }

        $query = $result->simplePaginate();

        return response()->json($graphBuilder->execute($query, fields: $fields, onlyFields: $this->allowedFields(), options: $request->query()));
    }

    public function show(GraphBuilder $graphBuilder, Request $request): JsonResponse
    {
        $fields = request()->query('fields');

        return response()->json([
            'data' => $graphBuilder->execute($this->findBy($fields), fields: $fields, onlyFields: $this->allowedFields(), options: $request->query()),
        ]);
    }

    protected function allowedFields(): ?array
    {
        return null;
    }

    protected function findBy(string | array $fields): Model
    {
        $routeParams = request()->route()?->parameters() ?: [];
        $idFromParam = array_pop($routeParams);
        $keyName     = $this->model()->getKeyName();

        return app(QueryBuilder::class)->execute(
            $this->model(),
            $fields,
            [
                "filter_({$keyName})" => $idFromParam,
            ] + request()->query(),
        )->sole();
    }
}
