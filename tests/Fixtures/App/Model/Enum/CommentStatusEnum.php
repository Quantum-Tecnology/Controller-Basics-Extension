<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Enum;

enum CommentStatusEnum: int
{
    case DRAFT     = 1;
    case PUBLISHED = 2;
    case ARCHIVED  = 3;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED  => 'Archived',
        };
    }
}
