<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

class GraphQlService
{
    public function __construct(
        protected BuilderQuery $builderQuery,
        protected GraphQLPresenter $presenter,
        protected PaginationSupport $paginationSupport,
    ) {
    }

    public function paginate(
        Model $model,
        array $fields,
        array $filters = [],
        array $pagination = [],
        ?int $page = null,
        ?int $perPage = null,
        ?string $pageName = null,
    ): Collection {
        $limit = $this->paginationSupport->calculatePerPage($perPage, $model::class);

        $builder = $this->builderQuery->execute($model, $fields, $filters)->paginate(
            perPage: $limit,
            pageName: null !== $pageName && '' !== $pageName && '0' !== $pageName ? $pageName : 'page',
            page: $page,
        );

        return $this->formatPaginatedResponse($builder, $fields, $pagination);
    }

    public function simplePaginate(
        Model $model,
        array $fields,
        array $filters = [],
        array $pagination = [],
        ?int $page = null,
        ?int $perPage = null,
        ?string $pageName = null,
    ): Collection {
        $limit = $this->paginationSupport->calculatePerPage($perPage, $model::class);

        $builder = $this->builderQuery->execute($model, $fields, $filters)->simplePaginate(
            perPage: $limit,
            pageName: null !== $pageName && '' !== $pageName && '0' !== $pageName ? $pageName : 'page',
            page: $page,
        );

        return $this->formatPaginatedResponse($builder, $fields, $pagination);
    }

    public function sole(Model $model, array $fields, array $filters = [], array $pagination = []): Collection
    {
        $item = $this->builderQuery->execute($model, $fields, $filters)->sole();

        return collect($this->unique($item, $fields, $pagination));
    }

    public function first(Model $model, array $fields, array $filters = [], array $pagination = []): Collection
    {
        $item = $this->builderQuery->execute($model, $fields, $filters)->first();

        return collect($this->unique($item, $fields, $pagination));
    }

    protected function formatPaginatedResponse(
        Paginator $builder,
        array $fields,
        array $pagination
    ): Collection {
        $response = [];

        foreach ($builder as $item) {
            $response[] = $this->presenter->execute($item, $fields, $pagination);
        }

        $pagination = [
            'per_page'       => $builder->perPage(),
            'current_page'   => $builder->currentPage(),
            'has_more_pages' => $builder->hasMorePages(),
            'page_name'      => $builder->getOptions()['pageName'],
        ];

        if ($builder instanceof LengthAwarePaginator) {
            $pagination['total']     = $builder->total();
            $pagination['last_page'] = $builder->lastPage();
        }

        return collect([
            'data' => $response,
            'meta' => $pagination,
        ]);
    }

    protected function unique(Model $model, array $fields, array $pagination = []): array
    {
        return $this->presenter->execute($model, $fields, $pagination);
    }
}
