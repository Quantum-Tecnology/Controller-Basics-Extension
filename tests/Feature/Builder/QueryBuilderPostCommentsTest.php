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

test('it returns only the id field and title is null', function (): void {
    $post = Post::factory()->create();

    /** @var Post $response */
    $response = $this->builder->execute(new Post(), ['id'])->sole();

    expect($response)->title->toBeNull()
        ->id->toBe($post->id);
});

test('it loads nested relations as specified in fields', function (): void {
    $post = Post::factory()->hasLikes(5)->create();
    Comment::factory()->for($post)->count(3)->hasLikes(3)->create();

    /** @var Post $response */
    $response = $this->builder->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []])->sole();

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
    $response = $this->builder->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []])->sole();

    expect($response->comments_count)->toBe(30)
        ->and($response->comments->first()->likes_count)->toBe(4);
});

test('it returns comments count and limits loaded comments to 10', function (): void {
    $comment = Comment::factory()->for(Post::factory()->create())->count(30)->create();
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
    Comment::factory()->for(Post::factory()->create())->count(25)->create();

    $fields  = ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []];
    $options = [
        'page_offset_comments' => 3,
        'page_limit_comments'  => 4,
    ];

    /** @var Post $post */
    $post = $this->builder->execute(new Post(), $fields, $options)->sole();

    expect($post->comments_count)->toBe(25)
        ->and($post->comments->count())->toBe(4)
        ->and($post->comments->get(0))->id->toBe(4)
        ->and($post->comments->get(1))->id->toBe(5);
});

test('it paginates likes of a nested relation and returns correct counts', function (): void {
    Comment::factory()->hasLikes(10)->create();

    $fields  = ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []];
    $options = [
        'page_offset_comments_likes' => 2,
        'page_limit_comments_likes'  => 2,
    ];

    /** @var Post $post */
    $post = $this->builder->execute(new Post(), $fields, $options)->sole();

    $comment = $post->comments->get(0);

    expect($comment->likes_count)->toBe(10)
        ->and($comment->likes->count())->toBe(2)
        ->and($comment->likes->get(0))->id->toBe(3)
        ->and($comment->likes->get(1))->id->toBe(4);
});

test('it orders comments and nested likes in descending order', function (): void {
    $comment = Comment::factory()->for(Post::factory()->create())->count(5)->create();
    $comment->first()->likes()->createMany([
        ['like' => 1],
        ['like' => 2],
        ['like' => 1],
        ['like' => 5],
    ]);

    /** @var Post $post */
    $post = $this->builder->execute(new Post(), ['id', 'comments' => ['likes' => ['comment' => []]], 'author' => []], [
        'order_column_comments'          => 'id',
        'order_direction_comments'       => 'desc',
        'order_column_comments_likes'    => 'id',
        'order_direction_comments_likes' => 'desc',
    ])->sole();

    $comments = $post->comments;
    $likes    = $post->comments->get(4)->likes;

    expect($comments->get(4))->id->toBe(1)
        ->and($comments->get(0))->id->toBe(5)
        ->and($likes->get(3))->id->toBe(1)
        ->and($likes->get(0))->id->toBe(4);
});

test('it orders comments by body in ascending and descending order', function (): void {
    $post = Post::factory()->create();

    Comment::factory()->for($post)->create(['body' => 'b']);
    Comment::factory()->for($post)->create(['body' => 'a']);

    /** @var Post $post */
    $post = $this->builder->execute(new Post(), ['id', 'comments'], [
        'order_column_comments'    => 'body',
        'order_direction_comments' => 'asc',
    ])->sole();

    $comments = $post->comments;

    expect($comments->get(0))->body->toBe('a')
        ->and($comments->get(1))->body->toBe('b');

    /** @var Post $post */
    $post = $this->builder->execute(new Post(), ['id', 'comments'], [
        'order_column_comments'    => 'body',
        'order_direction_comments' => 'desc',
    ])->sole();

    $comments = $post->comments;

    expect($comments->get(0))->body->toBe('b')
        ->and($comments->get(1))->body->toBe('a');
});

test('it loads nested comments and likes with correct counts', function (): void {
    Comment::factory()->hasLikes(10)->create();

    /** @var Post $response */
    $response = $this->builder->execute(new Post(), ['id', 'comments' => ['likes' => ['comment']], 'author'], [
        'order_column_comments'          => 'id',
        'order_direction_comments'       => 'desc',
        'order_column_comments_likes'    => 'id',
        'order_direction_comments_likes' => 'desc',
    ])->sole();

    $likes = $response->comments->get(0);

    expect($likes->likes_count)->toBe(10)
        ->and($likes->likes->get(0))->id->toBe(10)
        ->and($likes->likes->get(9))->id->toBe(1);
});

test('it filters comments and nested likes by id using custom filter options', function (): void {
    $post = Post::factory()->create();
    Comment::factory()->for($post)->hasLikes(5)->create();
    Comment::factory()->for($post)->hasLikes(5)->create();
    Comment::factory()->for($post)->create();
    Comment::factory()->for($post)->create();

    /** @var Post $response */
    $response = $this->builder->execute(new Post(), ['id', 'comments' => ['likes' => ['comment']], 'author'], [
        'filter_comments(id,<)'        => 2,
        'filter_comments_likes(id,<=)' => 2,
    ])->sole();

    $comments = $response->comments;

    expect($response)->comments_count->toBe(1)
        ->and($comments->count())->toBe(1)
        ->and($comments->get(0)->likes_count)->toBe(2)
        ->and($comments->get(0)->likes->count())->toBe(2);
});

describe('Testing together some certain methods', function (): void {
    beforeEach(function (): void {
        $this->refClass = new ReflectionClass(QueryBuilder::class);
        $this->instance = $this->refClass->newInstanceWithoutConstructor();
    });

    it('generateIncludes returns correct includes and closures for nested relations', function (): void {
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
