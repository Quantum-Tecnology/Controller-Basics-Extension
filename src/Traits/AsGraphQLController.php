<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Builder\GraphBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

trait AsGraphQLController
{
    abstract protected function model(): Model;

    public function index(QueryBuilder $queryBuilder, GraphBuilder $graphBuilder, Request $request): Collection
    {
        $result = $queryBuilder->execute($this->model(), $request->query('fields'), $request->query());

        if ($request->query('order_column')) {
            $result->orderBy($request->query('order_column'), $request->query('order_direction'));
        }

        $query = $result->simplePaginate();

        return $graphBuilder->execute($query, $request->query('fields'), $this->allowedFields());
    }

    public function show(GraphBuilder $graphBuilder, Request $request): Collection
    {
        $response = $this->findBy($request->query('fields'))->sole();

        return $graphBuilder->execute($response, $request->query('fields'), $request->query());
    }

    protected function allowedFields(): ?array
    {
        return null;
    }

    protected function findBy(string | array $fields): Builder
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
        );
    }
}
