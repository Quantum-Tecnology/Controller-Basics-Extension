<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\LikeFactory;

final class PostLike extends Model
{
    use HasFactory;

    protected static function newFactory(): LikeFactory
    {
        return LikeFactory::new();
    }
}
