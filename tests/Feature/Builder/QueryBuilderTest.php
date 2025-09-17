<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns the created post with correct title', function () {
    $post = Post::factory()->create();

    $response = $this->builder->execute(new Post())->sole();

    expect($response)->title->toBe($post->title);
});

test('it returns only the id field and title is null', function () {
    $post = Post::factory()->create();

    $response = $this->builder->fields(['id'])->execute(new Post())->sole();

    expect($response)->title->toBeNull()
        ->id->toBe($post->id);
});

test('it loads nested relations as specified in fields', function () {
    $post = Post::factory()->hasLikes(5)->create();
    Comment::factory()->for($post)->count(3)->hasLikes(3)->create();

    /** @var Post $response */
    $response = $this->builder->fields(['id', 'comments' => ['likes' => []], 'author' => []])->execute(new Post())->sole();

    expect(array_keys($response->getRelations()))->toBe(['comments', 'author'])
        ->and(array_keys($response->comments->first()->getRelations()))->toBe(['likes']);
});
