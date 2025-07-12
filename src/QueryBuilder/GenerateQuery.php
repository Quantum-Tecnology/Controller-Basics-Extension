<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\QueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GenericPresenter;

final readonly class GenerateQuery
{
    public function __construct(
        private ?Model $model = null,
        private ?object $classCallable = null,
        private ?string $action = null,
    ) {
    }

    public function execute(
        string $fields = '',
        array $pagination = [],
        array $filters = [],
    ): Builder {
        $query = $this->model->query();

        $genericPresenter = app(GenericPresenter::class);

        $this->addWhereWithFilters($query, $filters[$this->model::class] ?? []);

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

        if (filled($allCount = $genericPresenter->getWithCount($this->model, $allIncludes, $filters))) {
            $query->withCount($allCount);
        }

        return $query;
    }

    /**
     * Apply filters to Query Builder, treating simple filters and filters in nested relationships via whereHas.
     *
     * @param array $filters Exemplo: ['id' => ['=' => 5], 'comments.status' => ['in' => [1,2]], ...]
     */
    public function addWhereWithFilters(
        Builder | Relations\HasMany | Relations\BelongsToMany $query,
        array $filters = []
    ): void {
        if (blank($filters)) {
            return;
        }
        $model = $query->getModel();
        $table = $model->getTable();

        foreach ($filters as $column => $value) {
            foreach ($value as $operator => $data) {
                match ($operator) {
                    '='     => $query->whereIn("{$table}.{$column}", $data),
                    default => $query->where("{$table}.{$column}", $operator, $data[0]),
                };
            }
        }
    }
}
