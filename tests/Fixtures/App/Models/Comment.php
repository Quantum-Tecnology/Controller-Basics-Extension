<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\CommentFactory;

final class Comment extends BaseModel
{
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
