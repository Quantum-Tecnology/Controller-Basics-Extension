<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Tag;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns post with tags and tags count', function () {
    $tags = Tag::factory(20)->create();
    $post = Post::factory()->create();
    $post->tags()->attach($tags);

    $response = $this->builder->execute(new Post(), 'id tags')->sole();

    expect($response->tags_count)->toBe(20)
        ->and($response->tags->count())->toBe(10);
});
