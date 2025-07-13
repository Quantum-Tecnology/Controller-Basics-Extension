<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Services\GraphQlService;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function (): void {
    $this->service = app(GraphQlService::class);

    $this->post    = Post::factory()->create();
    $this->comment = Comment::factory(25)->for($this->post)->create();
});

test('it paginates comments on the first page', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '<=' => [20],
            ],
        ],
    ];

    $post = $this->service->paginate(new Post(), $fields, $filters);
    expect($post['data'][0]['data']['comments']['meta'])
        ->toBe([
            'total'          => 20,
            'per_page'       => 10,
            'current_page'   => 1,
            'last_page'      => 2,
            'has_more_pages' => true,
            'page_name'      => 'page_comments',
        ])
        ->and($post['meta'])->toBe([
            'per_page'       => 10,
            'current_page'   => 1,
            'has_more_pages' => false,
            'page_name'      => 'page',
            'total'          => 1,
            'last_page'      => 1,
        ]);
});

test('it paginates comments on the second page', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '<=' => [20],
            ],
        ],
    ];

    $post = $this->service->paginate(new Post(), $fields, $filters, [
        'comments' => [
            'page' => 2,
        ],
    ]);

    expect($post['data'][0]['data']['comments']['meta'])->toBe([
        'total'          => 20,
        'per_page'       => 10,
        'current_page'   => 2,
        'last_page'      => 2,
        'has_more_pages' => false,
        'page_name'      => 'page_comments',
    ]);
});

test('it simples paginates comments on the first page', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '<=' => [20],
            ],
        ],
    ];

    $post = $this->service->simplePaginate(new Post(), $fields, $filters);
    expect($post['data'][0]['data']['comments']['meta'])
        ->toBe([
            'total'          => 20,
            'per_page'       => 10,
            'current_page'   => 1,
            'last_page'      => 2,
            'has_more_pages' => true,
            'page_name'      => 'page_comments',
        ])
        ->and($post['meta'])->toBe([
            'per_page'       => 10,
            'current_page'   => 1,
            'has_more_pages' => false,
            'page_name'      => 'page',
        ]);
});

test('it simple paginates comments on the second page', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '<=' => [20],
            ],
        ],
    ];

    $post = $this->service->simplePaginate(new Post(), $fields, $filters, [
        'comments' => [
            'page' => 2,
        ],
    ]);

    expect($post['data'][0]['data']['comments']['meta'])->toBe([
        'total'          => 20,
        'per_page'       => 10,
        'current_page'   => 2,
        'last_page'      => 2,
        'has_more_pages' => false,
        'page_name'      => 'page_comments',
    ]);
});
