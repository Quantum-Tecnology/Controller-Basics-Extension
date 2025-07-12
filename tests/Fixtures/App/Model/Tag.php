<?php

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\TagFactory;

class Tag extends Model
{
    use HasFactory;

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
