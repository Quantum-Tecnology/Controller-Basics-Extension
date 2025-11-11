<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use QuantumTecnology\ControllerBasicsExtension\Models\ByFilterTrait;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Enum\PostStatusEnum;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\PostFactory;

final class Post extends BaseModel
{
    use ByFilterTrait;

    protected $casts = [
        'meta'   => 'array',
        'status' => PostStatusEnum::class,
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function featuredImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('type', 'featured');
    }

    public function galleryImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('type', 'gallery');
    }

    public function morphTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
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
