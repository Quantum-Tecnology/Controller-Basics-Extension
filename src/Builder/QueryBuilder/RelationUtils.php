<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

use Illuminate\Database\Eloquent\Model;

final class RelationUtils
{
    public static function nestedDotPaths(array $fields, string $prefix = ''): array
    {
        $paths = [];

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $current = '' !== $prefix && '0' !== $prefix ? "$prefix.$key" : $key;
                $paths[] = $current;
                $paths   = array_merge($paths, self::nestedDotPaths($value, $current));
            }
        }

        return $paths;
    }

    public static function resolveLastRelation(Model $model, string $path)
    {
        $currentModel = $model;
        $relation     = null;

        foreach (explode('.', $path) as $segment) {
            if (!method_exists($currentModel, $segment)) {
                $relation = null;

                break;
            }

            $relation = $currentModel->{$segment}();

            if (method_exists($relation, 'getRelated')) {
                $currentModel = $relation->getRelated();
            } else {
                break;
            }
        }

        return $relation;
    }
}
