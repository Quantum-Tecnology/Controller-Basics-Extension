<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
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
        GraphQlService $graphQlService,
        FieldSupport $fieldSupport,
        FilterSupport $filterSupport,
        PaginationSupport $paginationSupport,
    ): JsonResponse {
        $p       = $request->route()?->parameters() ?: [];
        $key     = $this->model()->getKeyName();
        $id      = array_pop($p);
        $queries = ["filter({$key})" => $id] + $request->query();

        $filters = $filterSupport->parse($queries + $this->filterRouteParams($p));

        $response = $graphQlService->sole(
            $this->model(),
            $fieldSupport->parse($queries['fields'] ?? ''),
            $filters,
            $paginationSupport->parse($queries),
        );

        return response()->json($response);
    }

    protected function filterRouteParams(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => ['filter(' . $key . ')' => $value])
            ->all();
    }
}
