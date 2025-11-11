<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\AuthorFactory;

final class Author extends BaseModel
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }
}
