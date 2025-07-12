<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\MediaFactory;

final class Media extends BaseModel
{
    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }
}
