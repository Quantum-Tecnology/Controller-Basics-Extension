<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\AuthorFactory;

final class Author extends BaseModel
{
    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }
}
