<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\PostFactory;

final class Post extends BaseModel
{
    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
