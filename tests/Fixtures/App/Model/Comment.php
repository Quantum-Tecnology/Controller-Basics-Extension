<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\CommentFactory;

final class Comment extends BaseModel
{
    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
