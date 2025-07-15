<?php

declare(strict_types = 1);

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\CommentLike;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Tag;

test('it returns a show of posts', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', $p))
        ->assertJson([])
        ->assertOk();
});

test('it returns only the requested fields for a post', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'id title']))
        ->assertJson([
            'data' => [
                'id'    => $p->id,
                'title' => $p->title,
            ],
        ])
        ->assertOk();
});

test('it returns only the requested fields and actions for a post', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'id can_update']))
        ->assertJson([
            'data' => [
                'id' => $p->id,
            ],
            'actions' => [
                'can_update' => false,
            ],
        ])
        ->assertOk();
});

test('it returns only the requested actions for a post', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'can_update']))
        ->assertJson([
            'actions' => [
                'can_update' => false,
            ],
        ])
        ->assertOk();
});

it('it creates a new post with only id and title fields', function (): void {
    postJson(route('posts.store', [
        'fields' => 'id title',
    ]), [
        'title'  => 'create a new post',
        'author' => [
            'name' => fake()->name,
        ],
        'comments' => [
            [
                'body' => 'test comment',
            ],
        ],
    ])->ddJson()
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
            ],
        ])
        ->assertCreated();
});

it('it creates a new post with meta and comments', function (): void {
    postJson(route('posts.store', [
        'fields' => 'id title',
    ]), [
        'title'     => 'create a new post',
        'author_id' => Author::factory()->create()->id,
        'meta'      => ['test'],
        'tags'      => [
            [
                'name' => 'test',
            ],
        ],
        'comments' => [
            [
                'body'  => 'test comment',
                'likes' => [
                    [
                        'like' => 1,
                    ],
                    [
                        'like' => 3,
                    ],
                ],
            ],
        ],
    ])->assertJsonStructure([
        'data' => [
            'id',
            'title',
        ],
    ])
        ->assertCreated();

    assertDatabaseCount(Tag::class, 1);
    assertDatabaseCount(Comment::class, 1);
    assertDatabaseCount(CommentLike::class, 2);
});

it('it updated a new post with only id and title fields', function (): void {
    $post = Post::factory()->create();

    putJson(route('posts.update', [
        'fields' => 'id title',
        'post'   => $post->id,
    ]), [
        'title'     => 'create a new post',
        'author_id' => Author::factory()->create()->id,
        'meta'      => ['test'],
        'comments'  => [
            [
                'body'  => 'test comment',
                'likes' => [
                    [
                        'like' => 1,
                    ],
                    [
                        'like' => 3,
                    ],
                ],
            ],
        ],
    ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
            ],
        ])
        ->assertOk();
});

it('it deleted a new post with only id and title fields', function (): void {
    $post = Post::factory()->create();

    deleteJson(route('posts.destroy', [
        'post' => $post->id,
    ]))
        ->assertNoContent();

    assertSoftDeleted($post);
});
