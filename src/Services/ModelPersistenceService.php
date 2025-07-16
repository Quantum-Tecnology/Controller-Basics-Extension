<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Str;

class ModelPersistenceService
{
    public function execute(Model $model, array $dataValues): Model
    {
        $dataChildren = [];
        $dataFather   = [];

        foreach ($dataValues as $key => $value) {
            $keyCamel = Str::camel($key);

            if (
                is_array($value)
                && method_exists($model, $keyCamel)
                && $model->{$keyCamel}() instanceof Relations\Relation
            ) {
                if ($model->{$keyCamel}() instanceof Relations\BelongsTo) {
                    $dataFather[$key] = [
                        'model' => $model->{$keyCamel}()->getRelated(),
                        'key'   => $model->{$keyCamel}()->getForeignKeyName(),
                        'value' => $value,
                    ];
                } else {
                    $dataChildren[$key] = $value;
                }
                unset($dataValues[$key]);
            }
        }

        foreach ($dataFather as $value) {
            $dataValues[$value['key']] = $this->execute(new $value['model'](), $value['value']);
        }

        $model->fill($dataValues);
        $model->save();

        foreach ($dataChildren as $key => $value) {
            $cloneModel = $model;

            $keyCamel       = Str::camel($key);
            $typeRelation   = $cloneModel->{$keyCamel}();
            $classRelated   = $cloneModel->{$keyCamel}()->getRelated();
            $idDataChildren = [];

            foreach ($value as $value2) {
                $dataArray = [];

                foreach ($value2 as $key3 => $value3) {
                    $key3Camel = Str::camel($key3);

                    if (
                        is_array($value3)
                        && method_exists($classRelated, $key3Camel)
                        && $classRelated->{$key3Camel}() instanceof Relations\Relation
                    ) {
                        $dataArray[$key3] = $value3;
                        unset($value2[$key3]);
                    }
                }

                [$idDataChildren] = $this->persistRelatedModels(
                    $cloneModel,
                    $classRelated,
                    $typeRelation,
                    $keyCamel,
                    $value2,
                    $dataArray,
                    $idDataChildren,
                );
            }

            if (filled($idDataChildren)) {
                $typeRelation->attach($idDataChildren);
            }
        }

        return $model;
    }

    public function persistHasManyRelation(string $keyCamel, Model $cloneModel, array $value2, array $dataArray): void
    {
        $modelInternal = $cloneModel->{$keyCamel}();
        $idModel       = $modelInternal->getRelated()->getKeyName();

        if (array_key_exists($idModel, $value2) && filled($value2[$idModel])) {
            $newModel = $cloneModel
                ->{$keyCamel}()
                ->where($idModel, $value2[$idModel])
                ->sole();
            $newModel->fill($value2);
        } else {
            $newModel = $modelInternal->create($value2);
        }
        $this->execute($newModel, $dataArray);
    }

    protected function persistRelatedModels(
        Model $cloneModel,
        Model $classRelated,
        Relations\Relation $typeRelation,
        string $keyCamel,
        array $value2,
        array $dataArray,
        array $idDataChildren,
    ): array {
        match (get_class($typeRelation)) {
            Relations\HasMany::class       => $this->persistHasManyRelation($keyCamel, $cloneModel, $value2, $dataArray),
            Relations\BelongsToMany::class => [$idDataChildren] = $this->persistBelongsToManyRelation($value2, $idDataChildren, $classRelated),
            default                        => null,
        };

        return [$idDataChildren];
    }

    protected function persistBelongsToManyRelation(array $value2, array $idDataChildren, Model $classRelated): array
    {
        ksort($value2);

        $name = json_encode($value2, JSON_THROW_ON_ERROR);

        if (!isset($idDataChildren[$name])) {
            $idDataChildren[$name] = $classRelated->create($value2);
        }

        return [$value2, $idDataChildren];
    }
}
