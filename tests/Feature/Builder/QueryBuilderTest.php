<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns the created post with correct title', function () {
    $post = Post::factory()->create();

    $response = $this->builder->execute(new Post())->sole();

    expect($response)->title->toBe($post->title);
});

test('it returns only the id field and title is null', function () {
    $post = Post::factory()->create();

    $response = $this->builder->execute(new Post(), ['id'])->sole();

    expect($response)->title->toBeNull()
        ->id->toBe($post->id);
});

describe('Testing together some certain methods', function () {
    beforeEach(function () {
        $this->refClass = new ReflectionClass(QueryBuilder::class);
        $this->instance = $this->refClass->newInstanceWithoutConstructor();
    });

    it('generateIncludes returns correct includes and closures for nested relations', function () {
        $method = $this->refClass->getMethod('generateIncludes');
        $method->setAccessible(true);

        $property = $this->refClass->getProperty('withCount');
        $property->setAccessible(true);

        $result = $method->invoke($this->instance, new Post(), [
            'author',
            'comments',
            'comments.likes',
            'comments.likes.comment',
            'comments.likes.comment.likes',
        ]);

        expect($result[0])->toBe('author')
            ->and($result[1])->toBe('comments.likes.comment')
            ->and($result['comments'])->toBeInstanceOf(Closure::class)
            ->and($result['comments.likes'])->toBeInstanceOf(Closure::class)
            ->and($result['comments.likes.comment.likes'])->toBeInstanceOf(Closure::class)
            ->and(array_keys($property->getValue($this->instance)))->toBe([
                'comments',
                'comments.likes',
                'comments.likes.comment.likes',
            ]);
    });
});
