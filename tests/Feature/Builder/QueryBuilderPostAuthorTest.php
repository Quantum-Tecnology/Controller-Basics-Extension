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
