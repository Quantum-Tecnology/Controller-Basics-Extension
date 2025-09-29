<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support\ApplyFilter;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support\FieldParser;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support\FilterParser;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support\IncludesBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support\RelationUtils;

class QueryBuilder
{
    protected array $withCount = [];

    public function execute(Model $model, string | array $fields = [], array $options = []): EloquentBuilder
    {
        $query = $model->query();

        if (is_string($fields)) {
            $fields = (new FieldParser())->normalize($fields);
        }

        // Collect root-level scalar fields requested
        $fieldSelected = array_filter($fields, fn ($item): bool => !is_array($item));

        // Remove any relation names that might appear as scalars
        foreach ($fieldSelected as $key => $value) {
            if (method_exists($model, $value)) {
                unset($fieldSelected[$key]);
            }
        }

        // Extract include/filters/pagination before finalizing select so we can add FK for BelongsTo
        $pagination = $this->extractOptions($options, 'page_offset', 'page_limit');
        $order      = $this->extractOptions($options, 'order_column', 'order_direction');
        $filters    = FilterParser::extract($model, $options);
        $includes   = IncludesBuilder::build($model, $fields, $filters, $pagination, $order, $this->withCount);

        // If there are BelongsTo includes, make sure to select their foreign keys on the parent
        foreach ($includes as $key => $val) {
            $path = is_int($key) ? $val : $key;

            if (!is_string($path)) {
                continue;
            }
            $relation = RelationUtils::resolveLastRelation($model, $path);

            if ($relation instanceof BelongsTo) {
                $fk = $relation->getForeignKeyName();

                if (!in_array($fk, $fieldSelected, true)) {
                    $fieldSelected[] = $fk;
                }
            }
        }

        if (filled($includes)) {
            $query->with($includes);
        }

        if ([] !== $this->withCount) {
            // Only pass root-level counts to the query, preserving closures; nested counts are handled within relation closures
            $counts = [];

            foreach ($this->withCount as $k => $v) {
                if (!str_contains($k, '.')) {
                    if (true === $v) {
                        $counts[] = $k;
                    } else {
                        $counts[$k] = $v; // closure or constraints
                    }
                }
            }

            if ([] !== $counts) {
                $query->withCount($counts);
            }
        }

        when(array_key_exists($model::class, $filters), fn () => ApplyFilter::execute($query, $filters[$model::class]));

        return $query;
    }

    private function extractOptions(array $options, string ...$items): array
    {
        $result = [];

        if ([] === $items) {
            return $result;
        }

        foreach ($options as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            foreach ($items as $item) {
                $prefix = mb_rtrim($item, '_') . '_';

                if (str_starts_with($key, $prefix)) {
                    $group = mb_substr($key, mb_strlen($prefix));

                    if ('' === $group) {
                        continue;
                    }
                    $result[$group] ??= [];
                    $result[$group][$item] = $value;
                    ksort($result[$group]);
                }
            }
        }

        return $result;
    }
}
