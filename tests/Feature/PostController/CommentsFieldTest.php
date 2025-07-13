<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;

test('it returns a list of posts with selected comments fields', function (): void {
    $comment = Comment::factory()->create();

    getJson(route('posts.index', [
        'fields' => 'comments[id,body]',
    ]))->assertJson([
        'data' => [
            [
                'comments' => [
                    'data' => [
                        [
                            'id'   => $comment->id,
                            'body' => $comment->body,
                        ],
                    ],
                ],
            ],
        ],
    ])
        ->assertOk();
})->todo();

test('it returns a list of posts with all comments fields', function (): void {
    $comment = Comment::factory()->create();

    getJson(route('posts.index', [
        'fields' => 'comments[*]',
    ]))->assertJson([
        'data' => [
            [
                'comments' => [
                    'data' => [
                        [
                            'id'          => $comment->id,
                            'body'        => $comment->body,
                            'created_at'  => $comment->created_at->toDateTimeString(),
                            'updated_at'  => $comment->updated_at->toDateTimeString(),
                            'deleted_at'  => null,
                            'use_factory' => null,
                            'actions'     => [
                                'can_delete' => true,
                                'can_update' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ])
        ->assertOk();
})->todo();
