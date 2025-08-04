<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\TagFactory;

final class Tag extends BaseModel
{
    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
