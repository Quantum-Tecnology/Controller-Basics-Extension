<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Enum;

enum PostStatusEnum: int
{
    case DRAFT     = 1;
    case PUBLISHED = 2;
    case ARCHIVED  = 3;
}
