<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support\FieldParser as QueryBuilderFieldParser;

class GraphBuilder
{
    public function execute($data, array | string $fields): Collection
    {
        if (is_string($fields)) {
            $fields = QueryBuilderFieldParser::normalize($fields);
        }

        $newData   = $data;
        $unique    = $data instanceof Model;
        $paginator = $data instanceof Paginator;

        if ($unique) {
            $newData = collect([$newData]);
        }

        $response = $this->handleData($unique, $newData);

        $meta = [];

        if ($paginator) {

            if ($data instanceof Paginator) {
                $meta['meta'] = [
                    'per_page'     => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'from'         => $data->firstItem(),
                    'to'           => $data->lastItem(),
                    'path'         => $data->path(),
                ];
            }

            if ($data instanceof LengthAwarePaginator) {
                $meta['meta'] += [
                    'total'     => $data->total(),
                    'last_page' => $data->lastPage(),
                ];
            }
        }

        if ($unique) {
            return collect($response->first());
        }

        return collect([
            'data' => $response->toArray(),
        ] + $meta);
    }

    protected function handleData(bool $isUnique, $data): Collection
    {
        $response = collect();

        foreach ($data as $rs) {
            $dataResult = collect();

            foreach ($this->getAllModelAttributes($rs) as $field) {
                $value = match (true) {
                    $rs->{$field} instanceof BackedEnum        => $this->enum($rs->{$field}),
                    $rs->{$field} instanceof DateTimeInterface => $rs->{$field}->format('Y-m-d H:i:s'),
                    default                                    => $rs->{$field},
                };
                $dataResult->put($field, $value);
            }

            $response->push($isUnique ? $dataResult->toArray() : ['data' => $dataResult->toArray()]);
        }

        return $response;
    }

    private function getAllModelAttributes(Model $model): array
    {
        $attributes = $model->getAttributes();

        foreach ($model->getMutatedAttributes() as $key) {
            if (!array_key_exists($key, $attributes)) {
                $attributes[$key] = $model->{$key};
            }
        }

        return array_keys($attributes);
    }

    private function enum(BackedEnum $enum): mixed
    {
        if (method_exists($enum, 'label')) {
            return [
                'value' => $enum->value,
                'label' => __($enum->label()),
            ];
        }

        return $enum->value;
    }
}
