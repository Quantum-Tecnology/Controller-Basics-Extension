<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\CommentLikeFactory;

final class CommentLike extends BaseModel
{
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    protected static function newFactory(): CommentLikeFactory
    {
        return CommentLikeFactory::new();
    }
}
