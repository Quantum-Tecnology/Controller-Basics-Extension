<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

test('it returns a list of posts', function (): void {
    Post::factory()->create();

    getJson(route('posts.index'))
        ->assertJson([
            'data' => [
                [],
            ],
        ])
        ->assertOk();
});

test('it returns a list of posts with selected author fields', function (): void {
    $author = Author::factory()->create();
    Post::factory()->for($author)->create();

    getJson(route('posts.index', [
        'fields' => 'author{id name}',
    ]))
        ->assertJson([
            'data' => [
                [
                    'data' => [
                        'author' => [
                            'data' => [
                                'id'   => $author->id,
                                'name' => $author->name,
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

test('it returns a list of posts with all author fields', function (): void {
    $post   = Post::factory()->create();
    $author = $post->author;

    getJson(route('posts.index', [
        'fields' => 'author{*}',
    ]))
        ->assertJson([
            'data' => [
                [
                    'data' => [
                        'author' => [
                            'data' => [
                                'id'          => $author->id,
                                'name'        => $author->name,
                                'created_at'  => $author->created_at->toDateTimeString(),
                                'updated_at'  => $author->updated_at->toDateTimeString(),
                                'deleted_at'  => null,
                                'use_factory' => null,
                            ],
                            'actions' => [
                                'can_delete' => true,
                                'can_update' => false,
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
