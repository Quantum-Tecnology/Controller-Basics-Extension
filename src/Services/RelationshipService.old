<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class RelationshipService
{
    public function execute(Model $model, array $data): Model
    {
        [$attributes, $relations] = $this->splitAttributesAndRelations($model, $data);

        $model->fill($attributes);

        $model->save();

        foreach ($relations as $relationName => $relationData) {
            if (!method_exists($model, $relationName)) {
                continue;
            }

            $relation = $model->{$relationName}();

            if (!$relation instanceof Relations\Relation) {
                continue;
            }

            match (true) {
                $relation instanceof Relations\HasOne        => $this->handleHasOne($relation, $relationData),
                $relation instanceof Relations\HasMany       => $this->handleHasMany($relation, $relationData),
                $relation instanceof Relations\BelongsTo     => $this->handleBelongsTo($model, $relation, $relationName, $relationData),
                $relation instanceof Relations\BelongsToMany => $this->handleBelongsToMany($relation, $relationData),
                $relation instanceof Relations\MorphOne      => $this->handleMorphOne($relation, $relationData),
                $relation instanceof Relations\MorphMany     => $this->handleMorphMany($relation, $relationData),
                $relation instanceof Relations\MorphToMany   => $this->handleMorphToMany($relation, $relationData),
                default                                      => null,
            };
        }

        return $model->fresh();
    }

    protected function splitAttributesAndRelations(Model $model, array $data): array
    {
        $attributes = [];
        $relations  = [];

        foreach ($data as $key => $value) {
            if (
                method_exists($model, $key)
                && $model->{$key}() instanceof Relations\Relation
                && is_array($value)
                && (
                    0 === count($value)
                    || (array_keys($value) !== range(0, count($value) - 1))
                    || (count($value) > 0 && is_array($value[0]))
                )
            ) {
                $relations[$key] = $value;
            } else {
                $attributes[$key] = $value;
            }
        }

        return [$attributes, $relations];
    }

    protected function handleHasOne(Relations\HasOne $relation, array $value): void
    {
        $keyName = $relation->getRelated()->getKeyName();

        $related = isset($value[$keyName])
            ? $relation->getRelated()->findOrFail($value[$keyName])
            : $relation->getRelated()->newInstance();

        // Only fill attributes, do not call execute here to avoid saving child before parent
        $related->fill($value);

        // Save via relationship to let Laravel fill FK automatically
        $relation->save($related);
    }

    protected function handleHasMany(Relations\HasMany $relation, array $items): void
    {
        $existingIds = $relation->pluck($keyName = $relation->getRelated()->getKeyName())->all();
        $incomingIds = [];

        $foreignKeyName = $relation->getForeignKeyName();
        $parentKey      = $relation->getParent()->getKey();

        foreach ($items as $item) {
            if (isset($item[$keyName])) {
                $related = $relation->getRelated()->find($item[$keyName]) ?? $relation->getRelated()->newInstance();
            } else {
                $related = $relation->getRelated()->newInstance();
            }

            // Set FK manually before saving
            $related->{$foreignKeyName} = $parentKey;

            $related = $this->execute($related, $item);
            $relation->save($related);

            $incomingIds[] = $related->getKey();
        }

        $toDelete = array_diff($existingIds, $incomingIds);

        if (count($toDelete) > 0) {
            $relation->getRelated()->destroy($toDelete);
        }
    }

    protected function handleBelongsTo(Model $model, Relations\BelongsTo $relation, string $key, array $value): void
    {
        $keyName = $relation->getRelated()->getKeyName();

        if (isset($value[$keyName])) {
            $related = $relation->getRelated()->findOrFail($value[$keyName]);
            $related->fill($value);
            $related->save();
        } else {
            $related = $this->execute($relation->getRelated()->newInstance(), $value);
        }

        $model->{$key}()->associate($related);
        $model->save();
    }

    protected function handleBelongsToMany(Relations\BelongsToMany $relation, array $items): void
    {
        $keyName = $relation->getRelated()->getKeyName();
        $ids     = [];

        foreach ($items as $item) {
            $related = isset($item[$keyName])
                ? $relation->getRelated()->findOrFail($item[$keyName])
                : $this->execute($relation->getRelated()->newInstance(), $item);

            $ids[] = $related->getKey();
        }

        $relation->sync($ids);
    }

    protected function handleMorphOne(Relations\MorphOne $relation, array $value): void
    {
        $keyName = $relation->getRelated()->getKeyName();

        $related = isset($value[$keyName])
            ? $relation->getRelated()->findOrFail($value[$keyName])
            : $relation->getRelated()->newInstance();

        $related = $this->execute($related, $value);

        $relation->save($related);
    }

    protected function handleMorphMany(Relations\MorphMany $relation, array $items): void
    {
        $keyName     = $relation->getRelated()->getKeyName();
        $existingIds = $relation->pluck($relation->getRelated()->getKeyName())->all();
        $incomingIds = [];

        foreach ($items as $item) {
            if (isset($item[$keyName])) {
                $related = $relation->getRelated()->find($item[$keyName]) ?? $relation->getRelated()->newInstance();
            } else {
                $related = $relation->getRelated()->newInstance();
            }

            $related = $this->execute($related, $item);
            $relation->save($related);

            $incomingIds[] = $related->getKey();
        }

        $toDelete = array_diff($existingIds, $incomingIds);

        if (count($toDelete) > 0) {
            $relation->getRelated()->destroy($toDelete);
        }
    }

    protected function handleMorphToMany(Relations\MorphToMany $relation, array $items): void
    {
        $ids     = [];
        $keyName = $relation->getRelated()->getKeyName();

        foreach ($items as $item) {
            $related = isset($item[$keyName])
                ? $relation->getRelated()->findOrFail($item[$keyName])
                : $this->execute($relation->getRelated()->newInstance(), $item);

            $ids[] = $related->getKey();
        }

        $relation->sync($ids);
    }
}
