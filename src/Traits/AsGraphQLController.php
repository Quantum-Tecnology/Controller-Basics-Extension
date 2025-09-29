<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use QuantumTecnology\ControllerBasicsExtension\Builder\GraphBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

trait AsGraphQLController
{
    abstract protected function model(): Model;

    public function index(QueryBuilder $queryBuilder, GraphBuilder $graphBuilder, Request $request)
    {
        $query = $queryBuilder->execute($this->model(), $request->query('fields'))->simplePaginate();

        return $graphBuilder->execute($query, $request->query('fields'));
    }
}
