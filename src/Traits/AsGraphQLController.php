<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use QuantumTecnology\ControllerBasicsExtension\Builder\GraphBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Services\Interfaces\DeleteServiceInterface;
use QuantumTecnology\ControllerBasicsExtension\Services\Interfaces\IndexServiceInterface;
use QuantumTecnology\ControllerBasicsExtension\Services\Interfaces\StoreServiceInterface;
use QuantumTecnology\ControllerBasicsExtension\Services\Interfaces\UpdateServiceInterface;
use QuantumTecnology\ControllerBasicsExtension\Services\RelationshipService;

trait AsGraphQLController
{
    abstract protected function model(): Model;

    public function service(): ?string
    {
        return null;
    }

    public function index(QueryBuilder $queryBuilder, GraphBuilder $graphBuilder, Request $request): JsonResponse
    {
        $this->getDataRequest('index', true);

        $fields = request()->query('fields');

        $result = $this->getService() && $this->getService() instanceof IndexServiceInterface
            ? $this->getService()->index($fields, $request->search, $request->query())
            : $queryBuilder->execute($this->model(), $request->query('fields'), $request->query());

        if ($request->query('order_column')) {
            $result->orderBy($request->query('order_column'), $request->query('order_direction'));
        }

        $query = $result->simplePaginate();

        $data = $graphBuilder->execute($query, fields: $fields, onlyFields: $this->allowedFields(), options: $request->query());

        if (app()->isLocal()) {
            $data['allowed_fields'] = $this->allowedFields();
        }

        return response()->json($data);
    }

    public function show(GraphBuilder $graphBuilder, Request $request): JsonResponse
    {
        $fields = request()->query('fields');

        $data = [
            'data' => $graphBuilder->execute($this->findBy($fields), fields: $fields, onlyFields: $this->allowedFields(), options: $request->query()),
        ];

        if (app()->isLocal()) {
            $data['allowed_fields'] = $this->allowedFields();
        }

        return response()->json($data);
    }

    public function store(GraphBuilder $graphBuilder, Request $request): JsonResponse
    {
        $dataValues = $this->getDataRequest('store');

        $modelSave = $this->getService() && $this->getService() instanceof StoreServiceInterface
            ? $this->getService()->store($dataValues)
            : $this->execute($this->model(), $dataValues);

        $keyName    = $this->model()->getKeyName();
        $fields     = request()->query('fields', [$keyName]);
        $onlyFields = array_merge([$keyName], $this->allowedFields());

        $data = [
            'data' => $graphBuilder->execute($modelSave, fields: $fields, onlyFields: $onlyFields, options: $request->query()),
        ];

        if (app()->isLocal()) {
            $data['allowed_fields'] = $this->allowedFields();
        }

        return response()->json($data);
    }

    public function update(GraphBuilder $graphBuilder, Request $request): JsonResponse
    {
        $dataValues = $this->getDataRequest('update');
        $keyName    = $this->model()->getKeyName();
        $fields     = request()->query('fields', [$keyName]);
        $model      = $this->findBy($fields);
        $onlyFields = array_merge([$keyName], $this->allowedFields());

        $modelSave = $this->getService() && $this->getService() instanceof UpdateServiceInterface
            ? $this->getService()->update($model, $dataValues)
            : $this->execute($model, $dataValues);

        $data = [
            'data' => $graphBuilder->execute($modelSave, fields: $fields, onlyFields: $onlyFields, options: $request->query()),
        ];

        if (app()->isLocal()) {
            $data['allowed_fields'] = $this->allowedFields();
        }

        return response()->json($data);
    }

    public function destroy(GraphBuilder $graphBuilder, Request $request): Response
    {
        $this->getDataRequest('destroy', true);

        $fields = request()->query('fields');

        $model = $this->findBy($fields);

        DB::transaction(fn () => $modelSave = $this->getService() && $this->getService() instanceof DeleteServiceInterface
            ? $this->getService()->delete($model)
            : $model->delete());

        return response()->noContent();
    }

    protected function allowedFields(): ?array
    {
        return null;
    }

    protected function findBy(string | array | null $fields = []): Model
    {
        $routeParams = request()->route()?->parameters() ?: [];
        $idFromParam = array_pop($routeParams);
        $keyName     = $this->model()->getKeyName();

        return app(QueryBuilder::class)->execute(
            $this->model(),
            $fields ?: [],
            [
                "filter_({$keyName})" => $idFromParam,
            ] + $this->filterRouteParams($routeParams) + request()->query(),
        )->sole();
    }

    protected function filterRouteParams(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn ($value, $key) => ['filter_(' . $key . ')' => $value])
            ->all();
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

            return $request->validated();
        }

        $value = mb_substr($value, 0, -10) . '\\' . ucfirst($action) . 'Request';

        if (class_exists($value)) {
            $request = app($value);

            return $request->validated();
        }

        if ($exact) {
            return [];
        }

        return self::getDataRequest();
    }

    protected function execute(Model $model, array $data)
    {
        return DB::transaction(fn () => app(RelationshipService::class)->execute($model, $data));
    }

    private function getService()
    {
        return when($this->service(), fn () => app($this->service()));
    }
}
