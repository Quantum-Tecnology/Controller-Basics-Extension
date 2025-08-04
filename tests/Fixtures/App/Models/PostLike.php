<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\PostLikeFactory;

final class PostLike extends BaseModel
{
    protected static function newFactory(): PostLikeFactory
    {
        return PostLikeFactory::new();
    }
}
