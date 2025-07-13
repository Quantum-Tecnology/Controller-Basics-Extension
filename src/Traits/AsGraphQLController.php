<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
    ) {
        $queries = $request->query();

        $response = $graphQlService->paginate(
            $this->model(),
            $fieldSupport->parse($queries['fields'] ?? ''),
            $filterSupport->parse($queries),
            $paginationSupport->parse($queries),
        );

        return response()->json($response);
    }
}
