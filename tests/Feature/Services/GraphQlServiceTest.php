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

test('it filters comments by id less than or equal to 3', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'comments' => [
            'id' => [
                '<=' => [20],
            ],
        ],
    ];

    $post = $this->service->paginate(new Post(), $fields, $filters);
    expect($post->first()['data']['comments']['meta'])->toBe([
        'total'        => 20,
        'per_page'     => 10,
        'current_page' => 1,
        'last_page'    => 2,
        'page_name'    => 'page_comments',
    ]);
});

test('', function (): void {
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

    expect($post->first()['data']['comments']['meta'])->toBe([
        'total'        => 20,
        'per_page'     => 10,
        'current_page' => 2,
        'last_page'    => 2,
        'page_name'    => 'page_comments',
    ]);
});
