<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;

class GraphQlService
{
    public function __construct(
        protected BuilderQuery $builderQuery,
        protected GraphQLPresenter $presenter
    ) {
    }

    public function paginate(Model $model, array $fields, array $filters = [], array $pagination = []): Collection
    {
        $builder = $this->builderQuery->execute($model, $fields, $filters)->paginate();

        $response = [];

        foreach ($builder as $item) {
            $response[] = $this->presenter->execute($item, $fields, $pagination);
        }

        return collect($response);
    }
}
