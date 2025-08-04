<?php

declare(strict_types = 1);

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Author;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Comment;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\CommentLike;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Enum\CommentStatusEnum;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Enum\PostStatusEnum;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Tag;

test('it returns a show of posts', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', $p))
        ->assertJson([])
        ->assertOk();
});

test('it returns paginated posts with correct meta', function (): void {
    Post::factory(50)->create();

    getJson(route('posts.index', [
        'page'     => 1,
        'per_page' => 5,
    ]))
        ->assertOk()
        ->assertJson([
            'meta' => [
                'per_page'  => 5,
                'last_page' => 10,
            ],
        ]);

    getJson(route('posts.index'))
        ->assertOk()
        ->assertJson([
            'meta' => [
                'per_page'  => 10,
                'last_page' => 5,
            ],
        ]);
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

test('it returns the requested fields including status as enum for a post', function (): void {
    $p = Post::factory()->create(['status' => PostStatusEnum::PUBLISHED->value]);

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'id title status']))
        ->assertJson([
            'data' => [
                'id'     => $p->id,
                'title'  => $p->title,
                'status' => [
                    'key'   => PostStatusEnum::PUBLISHED->name,
                    'value' => 2,
                    'label' => null,
                ],
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
        'status' => PostStatusEnum::DRAFT->value,
        'author' => [
            'name' => fake()->name,
        ],
        'comments' => [
            [
                'body' => 'test comment',
            ],
        ],
    ])->assertJsonStructure([
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
        'status'    => PostStatusEnum::PUBLISHED->value,
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
    ])
        ->assertJsonStructure([
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

it('it filters posts by title using the like operator', function (): void {
    Post::factory(3)->create();
    Post::factory()->create(['title' => 'testing_' . date('YmdHis')]);

    getJson(route('posts.index', [
        'fields'             => 'id title',
        'filter(title,like)' => 'testing_',
    ]))->assertJson([
        'meta' => [
            'total' => 1,
        ],
    ]);
});

it('it updates a post and its comments with only id and title fields', function (): void {
    $post = postJson(route('posts.store', [
        'fields' => 'id title comments {id status}',
    ]), [
        'title'     => 'create a new post',
        'author_id' => Author::factory()->create()->id,
        'status'    => PostStatusEnum::DRAFT->value,
        'comments'  => [
            [
                'body' => 'test comment',
                'status',
            ],
        ],
    ])->assertCreated();
    $idPost    = $post->json('data.id');
    $idComment = $post->json('data.comments.data.0.data.id');

    putJson(route('posts.update', [
        'fields' => 'id title comments {id}',
        'post'   => $idPost,
        'status' => PostStatusEnum::DRAFT->value,
    ]), [
        'title'     => 'create a new post',
        'author_id' => Author::factory()->create()->id,
        'comments'  => [
            [
                'id'     => $idComment,
                'body'   => 'update comment',
                'status' => [
                    'key'   => CommentStatusEnum::DRAFT->name,
                    'value' => 1,
                    'label' => CommentStatusEnum::DRAFT->label(),
                ],
            ],
        ],
    ])->assertOk();

    assertDatabaseCount(Comment::class, 1);
    assertDatabaseHas(Comment::class, [
        'id'   => $idComment,
        'body' => 'update comment',
    ]);
});

it('it updated a new post with only id and title fields', function (): void {
    $post = Post::factory()->create();

    putJson(route('posts.update', [
        'fields' => 'id title',
        'post'   => $post->id,
    ]), [
        'title'     => 'create a new post',
        'author_id' => Author::factory()->create()->id,
        'status'    => PostStatusEnum::DRAFT->value,
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

    assertDatabaseCount(Post::class, 1);
    assertDatabaseHas(Post::class, [
        'title' => 'create a new post',
    ]);
});

it('it deleted a new post with only id and title fields', function (): void {
    $post = Post::factory()->create();

    deleteJson(route('posts.destroy', [
        'post' => $post->id,
    ]))
        ->assertNoContent();

    assertSoftDeleted($post);
});
