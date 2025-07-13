<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Presenters;

use Illuminate\Database\Eloquent\Model;

class GraphQLPresenter
{
    public function execute(Model $model, array $fields, array $pagination = []): array
    {
        $data = [];
        $meta = [];

        foreach ($fields as $field) {
            if (str_starts_with($field, 'can_')) {
                $meta[$field] = $model->{$field};
            } else {
                $data[$field] = $model->{$field};
            }
        }

        $response = compact('data');

        if ($meta) {
            $response['meta'] = $meta;
        }

        return $response;
    }
}
