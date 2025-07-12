<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GenericPresenter;

final class GenerateQuery
{
    public function __construct(
        protected ?Model $model = null,
        protected ?object $classCallable = null,
        protected ?string $action = null,
    ) {
    }

    public function execute(
        string $fields = '',
        array $pagination = [],
        array $filters = [],
    ): Builder {
        $query = $this->model->query();

        $genericPresenter = app(GenericPresenter::class);

        // Aplica os filtros corretamente (tratando filtros aninhados)
        $this->addWhereWithFilters($query, $filters[$this->model::class] ?? []);

        // Pega os includes de relacionamentos (apenas strings, conforme ajuste anterior)
        if (filled($allIncludes = $genericPresenter->getIncludes(
            $this->model,
            $fields,
            $pagination,
            $filters,
            $this->classCallable,
            $this->action,
        ))) {
            $query->with($allIncludes);
        }

        // Pega os withCount para relacionamentos HasMany
        if (filled($allCount = $genericPresenter->getWithCount($this->model, $allIncludes))) {
            $query->withCount($allCount);
        }

        return $query;
    }

    /**
     * Aplica filtros no query builder, tratando filtros simples e filtros em relacionamentos aninhados via whereHas.
     *
     * @param array $filters Exemplo: ['id' => ['=' => 5], 'comments.status' => ['in' => [1,2]], ...]
     */
    public function addWhereWithFilters(Builder $query, array $filters = []): void
    {
        foreach ($filters as $field => $values) {
            if (str_contains($field, '.')) {
                // Campo em relacionamento aninhado — usa whereHas
                $this->addWhereHasFilter($query, $field, $values);

                continue;
            }

            foreach ($values as $operator => $data) {
                $model = $query->getModel();
                $table = $model->getTable();
                $camel = Str::camel($field);

                if (method_exists($model, $camel)) {
                    // Aqui você pode implementar filtro via método de relacionamento, se quiser.
                    // Por segurança, ignoramos para não chamar método direto.
                    continue;
                }

                match ($operator) {
                    'in'    => $query->whereIn("{$table}.{$field}", $data),
                    default => $query->where("{$table}.{$field}", $operator, $data),
                };
            }
        }
    }

    /**
     * Aplica filtro via whereHas para relacionamento aninhado.
     *
     * @param string $relationPath Exemplo: 'comments.commentsData'
     * @param array  $values       Operadores e valores para filtro no relacionamento
     */
    private function addWhereHasFilter(Builder $query, string $relationPath, array $values): void
    {
        $segments = explode('.', $relationPath);
        $field    = array_pop($segments);
        $relation = implode('.', $segments);

        $query->whereHas($relation, function (Builder $q) use ($field, $values) {
            foreach ($values as $operator => $data) {
                $model = $q->getModel();
                $table = $model->getTable();
                $camel = Str::camel($field);

                if (method_exists($model, $camel)) {
                    // Se quiser, trate filtros ainda mais aninhados recursivamente aqui
                    // Por simplicidade, ignoramos aqui para evitar complexidade
                    continue;
                }

                match ($operator) {
                    'in'    => $q->whereIn("{$table}.{$field}", $data),
                    default => $q->where("{$table}.{$field}", $operator, $data),
                };
            }
        });
    }
}
