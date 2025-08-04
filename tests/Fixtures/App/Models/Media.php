<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\MediaFactory;

final class Media extends BaseModel
{
    protected $fillable = ['path', 'type', 'mediable_id', 'mediable_type'];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }
}
