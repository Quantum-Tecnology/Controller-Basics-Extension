<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\LikeFactory;

final class CommentLike extends BaseModel
{
    protected static function newFactory(): LikeFactory
    {
        return LikeFactory::new();
    }
}
