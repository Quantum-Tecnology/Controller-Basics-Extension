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
                if (in_array($model->{$keyCamel}()::class, [
                    Relations\HasOne::class,
                    Relations\BelongsTo::class,
                ], true)) {
                    $dataFather[$key] = [
                        'model' => $model->{$keyCamel}()->getRelated(),
                        'value' => $value,
                        'key'   => $model->{$keyCamel}()->getForeignKeyName(),
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

                if ($typeRelation instanceof Relations\HasMany) {
                    $modelInternal = $cloneModel->{$keyCamel}();
                    $idModel       = $modelInternal->getRelated()->getKeyName();

                    if (array_key_exists($idModel, $value2) && filled($value2[$idModel])) {
                        $newModel = $cloneModel->{$keyCamel}()
                            ->where($idModel, $value2[$idModel])
                            ->sole();
                        $newModel->fill($value2);
                    } else {
                        $newModel = $modelInternal->create($value2);
                    }
                    $this->execute($newModel, $dataArray);
                }

                if ($typeRelation instanceof Relations\BelongsToMany) {
                    ksort($value2);

                    $name = json_encode($value2, JSON_THROW_ON_ERROR);

                    if (!isset($idDataChildren[$name])) {
                        $idDataChildren[$name] = $classRelated->create($value2);
                    }
                }
            }

            if (filled($idDataChildren)) {
                $typeRelation->attach($idDataChildren);
            }
        }

        return $model;
    }
}
