<?php

declare(strict_types = 1);

use Illuminate\Support\Collection;
use QuantumTecnology\ControllerBasicsExtension\Builder\GraphBuilder;
use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(function () {
    $this->queryBuilder = app(QueryBuilder::class);
    $this->graphBuilder = app(GraphBuilder::class);
});

test('0', function (): void {
    $p = Post::factory()->create();

    $fields   = 'id title';
    $response = $this->queryBuilder->execute(new Post(), fields: $fields)->sole();

    $response = $this->graphBuilder->execute($response, fields: $fields);

    expect($response->toArray())->toBe([
        'id'    => $p->id,
        'title' => $p->title,
    ]);
});

test('100', function (): void {
    $p = Post::factory(15)->create();

    $fields = 'id title created_at';

    $response = $this->queryBuilder->execute(new Post(), fields: $fields)->paginate(perPage: 2);

    $response = $this->graphBuilder->execute($response, fields: $fields);

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

test('101', function (): void {
    $p = Post::factory(15)->create();

    $fields = 'id title created_at';

    $response = $this->queryBuilder->execute(new Post(), fields: $fields)->simplePaginate(perPage: 2);

    $response = $this->graphBuilder->execute($response, fields: $fields);

    expect($response->toArray())->toBe([
        'data' => collect([0, 1])->map(function ($i) use ($p) {
            return [
                'data' => [
                    'id'    => $p->get($i)->id,
                    'title' => $p->get($i)->title,
                ],
            ];
        }),
        'meta' => [
            'per_page'     => 2,
            'current_page' => 1,
            'from'         => 1,
            'to'           => 2,
            'path'         => 'http://localhost',
        ],
    ]);
});

test('200', function (): void {
    Post::factory(15)->create();

    $fields = 'id title created_at';

    $response = $this->queryBuilder->execute(new Post(), fields: $fields)->get();

    $response = $this->graphBuilder->execute($response, fields: $fields);

    expect($response->toArray())->toBe([
        'data' => $response->each(function ($p) {
            return [
                'data' => [
                    'id'         => $p->id,
                    'title'      => $p->title,
                    'created_at' => $p->created_at->format('Y-m-d H:i:s'),
                ],
            ];
        }),
        'meta' => [
            'total' => 15,
        ],
    ]);
});

test('300', function (): void {
    $p = Post::factory()->hasComments(25)->create();

    $comments = $p->comments;

    $fields = 'id title comments { id }';

    $response = $this->queryBuilder->execute(new Post(), $fields)->get();

    $response = $this->graphBuilder->execute($response, fields: $fields);

    expect($response->toArray())->toBe([
        'data' => $response->each(function ($p) use ($comments) {
            return [
                'data' => [
                    'id'       => $p->id,
                    'title'    => $p->title,
                    'comments' => [
                        'data' => Collection::times(10, function ($i) use ($comments) {
                            return [
                                'data' => [
                                    'id' => $comments->get($i)->id,
                                ],
                            ];
                        }),
                    ],
                ],
            ];
        }),
        'meta' => [
            'total' => 15,
        ],
    ]);
});

test('400', function (): void {
    $p = Post::factory()->create();

    $author = $p->author;

    $field = 'id title author { id }';

    $response = $this->queryBuilder->execute(new Post(), fields: $field)->get();
    $response = $this->graphBuilder->execute($response, fields: $field);

    dd($response);

    expect($response->toArray())->toBe([
        'data' => $response->each(function ($p) use ($author) {
            return [
                'data' => [
                    'id'     => $p['data']['id'],
                    'title'  => $p['data']['title'],
                    'author' => [
                        'data' => [
                            'id' => $author->id,
                        ],
                    ],
                ],
            ];
        })->toArray(),
        'meta' => [
            'total' => 15,
        ],
    ]);
});
