<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function (): void {
    $this->post    = Post::factory()->create();
    $this->comment = Comment::factory(25)->for($this->post)->create();

    $this->builder = app(BuilderQuery::class);
});

test('it returns paginated comments for post', function (): void {
    $fields = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $post   = $this->builder->execute($this->post, $fields)->where('id', $this->post->id)->sole();
    expect($post->comments)->toHaveCount(10);
});

test('it paginates comments with per_page parameter', function (): void {
    $fields   = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $paginate = ['comments' => ['per_page' => 5]];

    $post = $this->builder->execute($this->post, $fields, [], $paginate)->where('id', $this->post->id)->sole();
    expect($post->comments)->toHaveCount(5);
});

test('it filters comments by id less than or equal to 3', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '<=' => [20],
            ],
        ],
    ];

    $post = $this->builder->execute($this->post, $fields, $filters)->where('id', $this->post->id)->sole();
    expect($post)->comments->toHaveCount(10)
        ->comments_count->toBe(20);
});

test('it filters comments by id less than or equal 3', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '=' => [3],
            ],
        ],
    ];

    $post = $this->builder->execute($this->post, $fields, $filters)->where('id', $this->post->id)->sole();
    expect($post->comments)->toHaveCount(1);
});
