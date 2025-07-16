<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Enum;

enum PostStatusEnum
{
    case DRAFT;
    case PUBLISHED;
    case ARCHIVED;
}
