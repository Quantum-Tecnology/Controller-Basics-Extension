<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Str;

class ModelPersistenceService
{
    public function execute(Model $model, array $inputData): Model
    {
        [$parentRelations, $childRelations, $ownAttributes] = $this->separateModelData($model, $inputData);

        $this->persistParentRelations($ownAttributes, $parentRelations);

        $model->fill($ownAttributes);
        $model->save();

        $this->persistChildRelations($model, $childRelations);

        return $model;
    }

    protected function persistRelatedModels(
        Model $parentModel,
        Model $relatedModel,
        Relations\Relation $relation,
        string $relationMethod,
        array $data,
        array $nestedData,
        array $attachedIds,
    ): array {
        if ($relation instanceof Relations\HasMany) {
            $this->persistHasMany($parentModel, $relationMethod, $data, $nestedData);
        }

        if ($relation instanceof Relations\BelongsToMany) {
            [$attachedIds] = $this->persistBelongsToMany($relatedModel, $data, $attachedIds);
        }

        return [$attachedIds];
    }

    protected function persistHasMany(Model $parentModel, string $relationMethod, array $data, array $nestedData): void
    {
        $relation     = $parentModel->{$relationMethod}();
        $relatedModel = $relation->getRelated();
        $primaryKey   = $relatedModel->getKeyName();

        if (!empty($data[$primaryKey] ?? null)) {
            $model = $relation->where($primaryKey, $data[$primaryKey])->sole();
            $model->fill($data);
        } else {
            $model = $relation->create($data);
        }

        $this->execute($model, $nestedData);
    }

    protected function persistBelongsToMany(Model $relatedModel, array $data, array $attachedIds): array
    {
        ksort($data);
        $dataKey = json_encode($data, JSON_THROW_ON_ERROR);

        if (!isset($attachedIds[$dataKey])) {
            $attachedIds[$dataKey] = $relatedModel->create($data)->getKey();
        }

        return [$attachedIds];
    }

    private function separateModelData(Model $model, array $inputData): array
    {
        $parentRelations = [];
        $childRelations  = [];
        $ownAttributes   = $inputData;

        foreach ($inputData as $key => $value) {
            $relationMethod = Str::camel($key);

            if (!is_array($value) || !method_exists($model, $relationMethod)) {
                continue;
            }

            $relation = $model->{$relationMethod}();

            if ($relation instanceof Relations\Relation) {
                if ($relation instanceof Relations\BelongsTo) {
                    $parentRelations[$key] = [
                        'model'       => $relation->getRelated(),
                        'foreign_key' => $relation->getForeignKeyName(),
                        'data'        => $value,
                    ];
                } else {
                    $childRelations[$key] = $value;
                }

                unset($ownAttributes[$key]);
            }
        }

        return [$parentRelations, $childRelations, $ownAttributes];
    }

    private function persistParentRelations(array &$ownAttributes, array $parentRelations): void
    {
        foreach ($parentRelations as $relation) {
            $relatedModel                            = new ($relation['model'])();
            $persistedParent                         = $this->execute($relatedModel, $relation['data']);
            $ownAttributes[$relation['foreign_key']] = $persistedParent->getKey();
        }
    }

    private function persistChildRelations(Model $model, array $childRelations): void
    {
        foreach ($childRelations as $relationKey => $childrenData) {
            $relationMethod = Str::camel($relationKey);
            $relation       = $model->{$relationMethod}();
            $relatedModel   = $relation->getRelated();

            $attachedIds = [];

            foreach ($childrenData as $childData) {
                [$nestedRelations, $cleanChildData] = $this->extractNestedRelations($relatedModel, $childData);

                [$attachedIds] = $this->persistRelatedModels(
                    $model,
                    $relatedModel,
                    $relation,
                    $relationMethod,
                    $cleanChildData,
                    $nestedRelations,
                    $attachedIds,
                );
            }

            if (!empty($attachedIds) && $relation instanceof Relations\BelongsToMany) {
                $relation->attach($attachedIds);
            }
        }
    }

    private function extractNestedRelations(Model $relatedModel, array $childData): array
    {
        $nestedRelations = [];

        foreach ($childData as $key => $value) {
            $relationMethod = Str::camel($key);

            if (is_array($value) && method_exists($relatedModel, $relationMethod)) {
                $relation = $relatedModel->{$relationMethod}();

                if ($relation instanceof Relations\Relation) {
                    $nestedRelations[$key] = $value;
                    unset($childData[$key]);
                }
            }
        }

        return [$nestedRelations, $childData];
    }
}
