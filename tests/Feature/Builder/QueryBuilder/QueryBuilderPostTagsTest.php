<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Tag;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns post with tags and tags count', function (): void {
    $tags = Tag::factory(20)->create(['name' => 'test']);
    $post = Post::factory()->create();
    $post->tags()->attach($tags);

    $response = $this->builder->execute(new Post(), 'id tags { name }')->sole();

    expect($response->tags_count)->toBe(20)
        ->and($response->tags->count())->toBe(10);
});

test('it returns post with attached tag name', function (): void {
    $tags = Tag::factory()->create(['name' => 'test from tag']);
    $post = Post::factory()->create();
    $post->tags()->attach($tags);

    $response = $this->builder->execute(new Post(), 'id tags { name }')->sole();

    expect($response->tags->first()->name)->toBe('test from tag');
});
