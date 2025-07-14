<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Support\FilterSupport;

beforeEach(function (): void {
    $this->support = new FilterSupport();
});

it('parses a simple filter with default operator', function (): void {
    $data   = ['filter_user(name)' => 'john'];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'name' => [
                '=' => ['john'],
            ],
        ],
    ]);
});

it('parses a filter with a custom operator', function (): void {
    $data   = ['filter_user(age,>)' => '18'];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'age' => [
                '>' => [18],
            ],
        ],
    ]);
});

it('parses multiple filters', function (): void {
    $data = [
        'filter_user(name)'     => 'john',
        'filter_user(age,>)'    => '18',
        'filter_status(status)' => 'active',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'name' => [
                '=' => ['john'],
            ],
            'age' => [
                '>' => [18],
            ],
        ],
        'status' => [
            'status' => [
                '=' => ['active'],
            ],
        ],
    ]);
});

it('ab', function (): void {
    $data = [
        'filter_user(name)'            => 'john',
        'filter_user(age,>)'           => '18',
        'filter_status(status)'        => 'active',
        'filter_post_comments(status)' => 'active',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'name' => [
                '=' => ['john'],
            ],
            'age' => [
                '>' => [18],
            ],
        ],
        'status' => [
            'status' => [
                '=' => ['active'],
            ],
        ],
        'post_comments' => [
            'status' => [
                '=' => [
                    0 => 'active',
                ],
            ],
        ],
    ]);
});

it('parses filter values as array', function (): void {
    $data   = ['filter_user(id)' => [1, 2, 3]];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'id' => [
                '=' => [1, 2, 3],
            ],
        ],
    ]);
});

it('parses filter values as comma-separated string', function (): void {
    $data   = ['filter_user(id)' => '1,2,3'];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'id' => [
                '=' => [1, 2, 3],
            ],
        ],
    ]);
});

it('parses filter values as pipe-separated string', function (): void {
    $data   = ['filter_user(id)' => '1|2|3'];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'id' => [
                '=' => [1, 2, 3],
            ],
        ],
    ]);
});

it('trims and casts numeric values', function (): void {
    $data   = ['filter_user(age)' => '  25  '];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'user' => [
            'age' => [
                '=' => [25],
            ],
        ],
    ]);
});

it('removes empty, null, or empty array values', function (): void {
    $data = [
        'filter_user(name)' => '',
        'filter_user(age)'  => null,
        'filter_user(id)'   => [],
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([]);
});

it('removes empty operators and fields after cleaning', function (): void {
    $filters = [
        'user' => [
            'name' => [
                '=' => [],
            ],
            'age' => [
                '=' => [25],
            ],
        ],
        'status' => [
            'status' => [
                '=' => [],
            ],
        ],
    ];

    $support    = new FilterSupport();
    $reflection = new ReflectionClass($support);
    $method     = $reflection->getMethod('cleanFilters');
    $method->setAccessible(true);

    $result = $method->invoke($support, $filters);

    expect($result)->toBe([
        'user' => [
            'age' => [
                '=' => [25],
            ],
        ],
    ]);
});

it('returns empty array for no matching filters', function (): void {
    $data   = ['foo' => 'bar'];
    $result = $this->support->parse($data);

    expect($result)->toBe([]);
});

it('parses filter for root model with default operator and multiple values', function (): void {
    $data   = ['filter(id)' => '1|2|3'];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        '[__model__]' => [
            'id' => [
                '=' => [1, 2, 3],
            ],
        ],
    ]);
});
