<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\PostFactory;

final class Post extends BaseModel
{
    protected $casts = [
        'meta' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function getCustomAttribute(): string
    {
        return 'custom_value';
    }

    public function customOld(): Attribute
    {
        return Attribute::get(fn (): string => 'custom_old');
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
