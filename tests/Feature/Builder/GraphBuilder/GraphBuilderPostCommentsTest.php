<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\GraphBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Author;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(function () {
    $this->queryBuilder = app(QueryBuilder::class);
    $this->graphBuilder = app(GraphBuilder::class);
});

test('returns post with id and title', function (): void {
    $p = Post::factory()->create();

    $fields       = 'id title';
    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields)->sole();

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields);

    expect($response->toArray())->toBe([
        'id'    => $p->id,
        'title' => $p->title,
    ]);
});

test('paginates posts with id, title, and created_at', function (): void {
    $p = Post::factory(15)->create();

    $fields = 'id title created_at';

    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields)->paginate(perPage: 2);

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields);

    expect($response->toArray())->toBe([
        'data' => collect([0, 1])->map(function ($i) use ($p) {
            return [
                'data' => [
                    'id'    => $p[$i]->id,
                    'title' => $p[$i]->title,
                ],
            ];
        })->toArray(),
        'meta' => [
            'per_page'     => 2,
            'current_page' => 1,
            'from'         => 1,
            'to'           => 2,
            'path'         => 'http://localhost',
            'total'        => 15,
            'last_page'    => 8,
        ],
    ]);
});

test('simple pagination returns correct post data', function (): void {
    $p = Post::factory(15)->create();

    $fields = 'id title created_at';

    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields)->simplePaginate(perPage: 2);

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields);

    expect($response->toArray())->toBe([
        'data' => collect([0, 1])->map(function ($i) use ($p) {
            return [
                'data' => [
                    'id'    => $p->get($i)->id,
                    'title' => $p->get($i)->title,
                ],
            ];
        })->toArray(),
        'meta' => [
            'per_page'     => 2,
            'current_page' => 1,
            'from'         => 1,
            'to'           => 2,
            'path'         => 'http://localhost',
        ],
    ]);
});

test('returns all posts with meta total', function (): void {
    $posts = Post::factory(15)->create();

    $fields = 'id title created_at';

    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields)->get();

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields);

    expect($response->toArray()['data'])->toBe($posts->map(function ($post) {
        return [
            'data' => [
                'id'         => $post->id,
                'title'      => $post->title,
                'created_at' => $post->created_at->format('Y-m-d H:i:s'),
            ],
        ];
    })->toArray());
});

test('returns post with author relationship', function (): void {
    $p = Post::factory()->create();

    $author = $p->author;

    $field = 'id title author { id }';

    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $field)->get();
    $response     = $this->graphBuilder->execute($queryBuilder, fields: $field)->toArray();

    expect($response['meta']['total'])->toBe(1)
        ->and($response['data'])->toBe([
            [
                'data' => [
                    'id'     => $p->id,
                    'title'  => $p->title,
                    'author' => [
                        'data' => [
                            'id' => $author->id,
                        ],
                    ],
                ],
            ],
        ]);
});

test('returns post with limited comments and meta', function (): void {
    $p = Post::factory()->hasComments(25)->create();

    $fields = 'id title comments { id }';

    $queryBuilder = $this->queryBuilder->execute(new Post(), $fields)->get();

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields)->toArray();

    $commentsMap = [
        'data' => $p->comments()->limit(10)->get()->map(function ($comment) {
            return [
                'data' => [
                    'id' => $comment->id,
                ],
            ];
        })->toArray(),
        'meta' => [
            'total' => 25,
            'page'  => 1,
        ],
    ];

    expect($response['data'])->toBe([
        [
            'data' => [
                'id'       => $p->id,
                'title'    => $p->title,
                'comments' => $commentsMap,
            ],
        ],
    ]);
});

test('returns post with paginated comments on page 2', function (): void {
    $p = Post::factory()->hasComments(25)->create();

    $fields = 'id title comments { id }';

    $options = [
        'page_offset_comments' => 2,
    ];

    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields, options: $options)->get();

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields, options: $options)->toArray();

    $comments = $response['data'][0]['data']['comments'];
    unset($response['data'][0]['data']['comments']);

    expect($response['data'])->toBe([
        [
            'data' => [
                'id'    => $p->id,
                'title' => $p->title,
            ],
        ],
    ])->and($comments['meta'])->toBe([
        'total' => 25,
        'page'  => 2,
    ]);
});

test('returns post with limited comments like and meta', function (): void {
    Comment::factory()->hasLikes(25)->create();

    $fields = 'id title comments { likes {id} }';

    $queryBuilder = $this->queryBuilder->execute(new Post(), $fields)->get();

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields)->toArray();

    expect($response['data'][0]['data']['comments']['data'][0]['data']['likes']['meta'])->toBe([
        'total' => 25,
        'page'  => 1,
    ]);
});

test('returns post with limited comments like and meta with options', function (): void {
    Comment::factory()->hasLikes(25)->create();

    $fields  = 'id title comments { likes {id} }';
    $options = [
        'page_offset_comments_likes' => 2,
    ];

    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields, options: $options)->get();

    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields, options: $options)->toArray();

    expect($response['data'][0]['data']['comments']['data'][0]['data']['likes']['meta'])->toBe([
        'total' => 25,
        'page'  => 2,
    ]);
});

test('100', function () {
    $a = Author::factory()->create();
    $p = Post::factory()->for($a)->create();
    $c = Comment::factory()->for($p)->create();

    $fields       = 'id title comments { id body } author { id name }';
    $queryBuilder = $this->queryBuilder->execute(new Post(), fields: $fields)->sole();

    //    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields, onlyFields: ['id', 'comments' => ['id'], 'author' => ['id']]);
    $response = $this->graphBuilder->execute($queryBuilder, fields: $fields);

    expect($response->toArray())->toBe([
        'id'       => $p->id,
        'comments' => [
            'data' => [
                0 => [
                    'data' => [
                        'id'   => $c->id,
                        'body' => $c->body,
                    ],
                ],
            ],
            'meta' => [
                'total' => 1,
                'page'  => 1,
            ],
        ],
        'author' => [
            'data' => [
                'id'   => $a->id,
                'name' => $a->name,
            ],
        ],
    ]);
});
