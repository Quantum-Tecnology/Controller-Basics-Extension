<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Tag;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns post with author', function () {
    $tags = Tag::factory(20)->create();
    $post = Post::factory()->create();
    $post->tags()->attach($tags);

    $response = $this->builder->execute(new Post(), 'id author { name }')->sole();

    expect($response->author)->name->not->toBeNull();
});
