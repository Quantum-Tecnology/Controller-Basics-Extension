<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    final public function getCanDeleteAttribute(): bool
    {
        return true;
    }

    final public function canUpdate(): Attribute
    {
        return Attribute::get(fn (): false => false);
    }
}
