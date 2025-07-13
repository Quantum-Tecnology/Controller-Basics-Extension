<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function (): void {
    $this->presenter = app(GraphQLPresenter::class);
    $this->post      = Post::factory()->create();
    $this->comment   = Comment::factory()->for($this->post)->create();
});

test('returns only requested fields in data', function (): void {
    $fields = ['id', 'comments' => ['id', 'body']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => [
            'id'       => $this->post->id,
            'comments' => [
                'data' => [
                    [
                        'data' => [
                            'id'   => $this->comment->id,
                            'body' => $this->comment->body,
                        ],
                    ],
                ],
                'meta' => [
                    'total' => 1,
                ],
            ],
        ],
    ]);
});

test('fields starting with can_ go to meta', function (): void {
    $fields = ['comments' => ['id', 'body', 'can_delete']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => [
            'comments' => [
                'data' => [
                    [
                        'data' => [
                            'id'   => $this->comment->id,
                            'body' => $this->comment->body,
                        ],
                        'meta' => [
                            'can_delete' => true,
                        ],
                    ],
                ],
                'meta' => [
                    'total' => 1,
                ],
            ],
        ],
    ]);
});

test('asterisk returns all fields and accessors', function (): void {
    $fields = ['comments' => ['*']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result['data']['comments']['data'][0])
        ->toMatchArray([
            'data' => [
                'id'          => $this->comment->id,
                'post_id'     => $this->comment->post_id,
                'body'        => $this->comment->body,
                'created_at'  => $this->comment->created_at,
                'updated_at'  => $this->comment->updated_at,
                'deleted_at'  => $this->comment->deleted_at,
                'use_factory' => null,
            ],
            'meta' => [
                'can_delete' => true,
                'can_update' => false,
            ],
        ]);
});
