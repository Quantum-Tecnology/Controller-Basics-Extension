<?php

declare(strict_types = 1);
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

beforeEach(function (): void {
    $this->support = new PaginationSupport();
});

it('parses deeply nested pagination keys', function (): void {
    $data = [
        'per_page_a.b.c.d' => '15',
        'page_a.b.c.d'     => '3',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'a' => [
            'b' => [
                'c' => [
                    'd' => [
                        'per_page' => 15,
                        'page'     => 3,
                    ],
                ],
            ],
        ],
    ]);
});

it('handles multiple nested relations at different levels', function (): void {
    $data = [
        'per_page_users'                => '10',
        'page_users'                    => '1',
        'per_page_users.posts'          => '5',
        'page_users.posts'              => '2',
        'per_page_users.posts.comments' => '20',
        'page_users.posts.comments'     => '4',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'users' => [
            'per_page' => 10,
            'page'     => 1,
            'posts'    => [
                'per_page' => 5,
                'page'     => 2,
                'comments' => [
                    'per_page' => 20,
                    'page'     => 4,
                ],
            ],
        ],
    ]);
});

it('handles missing per_page or page for some relations', function (): void {
    $data = [
        'per_page_items'     => '8',
        'per_page_items.sub' => '4',
        'page_items.sub'     => '2',
        // no page_items
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'items' => [
            'per_page' => 8,
            'sub'      => [
                'per_page' => 4,
                'page'     => 2,
            ],
        ],
    ]);
});

it('ignores unrelated keys and non-matching patterns', function (): void {
    $data = [
        'foo'                 => 'bar',
        'per_page_alpha.beta' => '6',
        'random_key'          => 'value',
        'page_alpha.beta'     => '1',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'alpha' => [
            'beta' => [
                'per_page' => 6,
                'page'     => 1,
            ],
        ],
    ]);
});

it('casts all values to int', function (): void {
    $data = [
        'per_page_x.y' => '12',
        'page_x.y'     => '5',
    ];
    $result = $this->support->parse($data);

    expect($result['x']['y']['per_page'])->toBeInt();
    expect($result['x']['y']['page'])->toBeInt();
});

it('returns empty array if no pagination keys are present', function (): void {
    $data = [
        'foo' => 'bar',
        'baz' => 'qux',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([]);
});
