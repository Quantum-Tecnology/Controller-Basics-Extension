<?php

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\CommentFactory;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\PostFactory;

class Comment extends Model
{
    use HasFactory;

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
