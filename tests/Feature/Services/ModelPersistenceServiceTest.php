<?php

declare(strict_types = 1);

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

use QuantumTecnology\ControllerBasicsExtension\Services\ModelPersistenceService;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Enum\PostStatusEnum;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Tag;

beforeEach(function () {
    $this->service = app(ModelPersistenceService::class);
});

it('saves a simple model', function () {
    $model  = Post::factory()->make();
    $result = $this->service->execute(new Post(), $model->toArray());
    expect($result->id)->not->toBeNull()
        ->and($result->title)->toBe($model->title);
});

it('saves belongsTo relation recursively', function () {
    $data = [
        'title'  => 'Post Title',
        'status' => PostStatusEnum::ARCHIVED->value,
        'author' => [
            'name' => 'Author Name',
        ],
    ];

    $result = $this->service->execute(new Post(), $data);

    expect($result->author_id)->not->toBeNull();
    assertDatabaseCount(Author::class, 1);
    assertDatabaseHas(Author::class, [
        'name' => 'Author Name',
    ]);
});

it('saves hasMany relation recursively', function () {
    $data = [
        'title'     => 'Post Title',
        'status'    => PostStatusEnum::ARCHIVED->value,
        'author_id' => Author::factory()->create()->id,
        'comments'  => [
            [
                'body' => 'Comment body',
                'tags' => [
                    [
                        'name' => 'testing',
                    ],
                ],
            ],
        ],
    ];

    $this->service->execute(new Post(), $data);

    assertDatabaseCount(Tag::class, 1);
    assertDatabaseCount(Comment::class, 1);
    assertDatabaseHas(Tag::class, [
        'name' => 'testing',
    ]);
    assertDatabaseHas(Comment::class, [
        'body' => 'Comment body',
    ]);
});

it('aa', function () {
    $post    = Post::factory()->create();
    $comment = Comment::factory()->for($post)->create();
    $this->service->execute($post, [
        'comments' => [
            [
                'id'   => $comment->id,
                'body' => 'updating comment',
            ],
        ],
    ]);

    assertDatabaseCount(Comment::class, 1);
    assertDatabaseHas(Comment::class, [
        'id'   => $comment->id,
        'body' => 'updating comment',
    ]);
});
