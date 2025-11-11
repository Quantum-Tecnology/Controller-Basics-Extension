<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(fn () => $this->builder = app(QueryBuilder::class));

test('it returns the created post with correct title', function (): void {
    $post = Post::factory()->create();

    $response = $this->builder->execute(new Post())->sole();

    expect($response)->title->toBe($post->title);
});

test('it loads nested relations as specified in fields', function (): void {
    $post = Post::factory()->hasLikes(5)->create();
    Comment::factory()->for($post)->count(3)->hasLikes(3)->create();

    /** @var Post $response */
    $response = $this->builder
        ->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []])
        ->sole();

    expect(array_keys($response->getRelations()))->toBe(['comments', 'author'])
        ->and(array_keys($response->comments->first()->getRelations()))->toBe(['likes']);
});

test('it creates a post with likes and comments', function (): void {
    $post    = Post::factory()->hasLikes(5)->create();
    $comment = Comment::factory()->for($post)->count(30)->create();
    $comment->first()->likes()->createMany([
        ['like' => 1],
        ['like' => 2],
        ['like' => 1],
        ['like' => 5],
    ]);

    /** @var Post $response */
    $response = $this->builder
        ->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []])
        ->sole();

    expect($response->comments_count)->toBe(30)
        ->and($response->comments->first()->likes_count)->toBe(4);
});

test('it returns comments count and limits loaded comments to 10', function (): void {
    $comment = Comment::factory()->for(Post::factory()->hasLikes(5)->create())->count(30)->create();
    $comment->first()->likes()->createMany([
        ['like' => 1],
        ['like' => 2],
        ['like' => 1],
        ['like' => 5],
    ]);

    /** @var Post $post */
    $post = $this->builder->execute(new Post(), ['id', 'comments'])->sole();

    expect($post->comments_count)->toBe(30)
        ->and($post->comments->count())->toBe(10);
});

test('it paginates nested relations and returns correct counts', function (): void {
    $comment = Comment::factory()->for(Post::factory()->hasLikes(5)->create())->count(25)->create();
    $comment->first()->likes()->createMany([
        ['like' => 1],
        ['like' => 2],
        ['like' => 1],
        ['like' => 5],
    ]);

    /** @var Post $post */
    $post = $this->builder
        ->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []], [
            'page_limit_comments' => 100,
        ])->sole();

    expect($post->comments_count)->toBe(25)
        ->and($post->comments->count())->toBe(24);
});

test('it orders comments and nested likes in descending order', function (): void {
    $comment = Comment::factory()->for(Post::factory()->hasLikes(5)->create())->count(5)->create();
    $comment->first()->likes()->createMany([
        ['like' => 1],
        ['like' => 2],
        ['like' => 1],
        ['like' => 5],
    ]);

    /** @var Post $post */
    $post = $this->builder
        ->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []], [
            'order_column_comments'          => 'id',
            'order_direction_comments'       => 'desc',
            'order_column_comments_likes'    => 'id',
            'order_direction_comments_likes' => 'desc',
        ])
        ->sole();

    $comments = $post->comments;
    $likes    = $post->comments->get(4)->likes;

    expect($comments->get(4))->id->toBe(1)
        ->and($comments->get(0))->id->toBe(5)
        ->and($likes->get(3))->id->toBe(1)
        ->and($likes->get(0))->id->toBe(4);
});
