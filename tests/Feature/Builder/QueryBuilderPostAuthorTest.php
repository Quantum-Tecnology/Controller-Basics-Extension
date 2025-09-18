<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Author;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns post with author', function () {
    $author = Author::factory()->create();
    Post::factory()->for($author)->create();

    $response = $this->builder->execute(new Post(), 'id author { name }')->sole();

    expect($response->author)->name->toBe($author->name);
});

test('it returns posts with author and posts count', function () {
    $author = Author::factory()->create();
    Post::factory(3)->for($author)->create();

    $response = $this->builder->execute(new Post(), 'id author { name posts {id author {name posts {id}}} }')->get();

    expect($response->get(0)->author->posts_count)->toBe(3)
        ->and($response->get(0)->author->posts->get(0)->author->posts->count())->toBe(3);
});
