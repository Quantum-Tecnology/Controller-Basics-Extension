<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\PostFactory;

final class Post extends BaseModel
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
