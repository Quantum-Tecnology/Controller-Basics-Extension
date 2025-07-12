<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\POstLikeFactory;

final class PostLike extends BaseModel
{
    protected static function newFactory(): POstLikeFactory
    {
        return POstLikeFactory::new();
    }
}
