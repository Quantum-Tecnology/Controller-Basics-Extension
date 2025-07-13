<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;

test('it returns a list of posts with selected comments fields', function (): void {
    $comment = Comment::factory()->create();

    getJson(route('posts.index', [
        'fields' => 'comments{ id,body }',
    ]))->assertJson([
        'data' => [
            [
                'data' => [
                    'comments' => [
                        'data' => [
                            [
                                'data' => [
                                    'id'   => $comment->id,
                                    'body' => $comment->body,
                                ],
                            ],
                        ],
                        'meta' => [
                            'total'          => 1,
                            'per_page'       => 10,
                            'current_page'   => 1,
                            'last_page'      => 1,
                            'has_more_pages' => false,
                            'page_name'      => 'page_comments',
                        ],
                    ],
                ],
            ],
        ],
        'meta' => [
            'per_page'       => 10,
            'current_page'   => 1,
            'has_more_pages' => false,
            'page_name'      => 'page',
            'total'          => 1,
            'last_page'      => 1,
        ],
    ])
        ->assertOk();
});

test('it returns a list of posts with all comments fields', function (): void {
    $comment = Comment::factory()->create();

    getJson(route('posts.index', [
        'fields' => 'comments{*}',
    ]))->assertJson([
        'data' => [
            [
                'data' => [
                    'comments' => [
                        'data' => [
                            [
                                'data' => [
                                    'id'          => $comment->id,
                                    'body'        => $comment->body,
                                    'created_at'  => $comment->created_at->toDateTimeString(),
                                    'updated_at'  => $comment->updated_at->toDateTimeString(),
                                    'deleted_at'  => null,
                                    'use_factory' => null,
                                ],
                                'actions' => [
                                    'can_delete' => true,
                                    'can_update' => false,
                                ],
                            ],
                        ],
                        'meta' => [
                            'total'          => 1,
                            'per_page'       => 10,
                            'current_page'   => 1,
                            'last_page'      => 1,
                            'has_more_pages' => false,
                            'page_name'      => 'page_comments',
                        ],
                    ],
                ],
            ],
        ],
        'meta' => [
            'per_page'       => 10,
            'current_page'   => 1,
            'has_more_pages' => false,
            'page_name'      => 'page',
            'total'          => 1,
            'last_page'      => 1,
        ],
    ])->assertOk();
});

test('it filters comments by id and returns only selected fields', function () {
    $comment01 = Comment::factory()->create();
    Comment::factory()->create();
    getJson(route('comments.index', [
        'post_id' => $comment01->post_id,
        'fields'  => 'id post_id',
    ]))->assertJson([
        'meta' => [
            'total' => 1,
        ],
    ]);
});

test('it returns 404 for comment not belonging to post and 200 for valid comment', function () {
    $comment01 = Comment::factory()->create();
    $comment02 = Comment::factory()->create();
    getJson(route('comments.show', [
        'post_id' => $comment01->post_id,
        'comment' => $comment02->id,
        'fields'  => 'id post_id',
    ]))->assertNotFound();

    getJson(route('comments.show', [
        'post_id' => $comment01->post_id,
        'comment' => $comment01->id,
        'fields'  => 'id post_id',
    ]))->assertJson([
        'data' => [
            'id'      => 1,
            'post_id' => 1,
        ],
    ]);
});
