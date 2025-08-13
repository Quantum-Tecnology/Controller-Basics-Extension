<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Support\FilterSupport;
use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

beforeEach(function (): void {
    $this->builder = new BuilderQuery(new FilterSupport(), new PaginationSupport());
    $reflection    = new ReflectionClass($this->builder);
    $this->method  = $reflection->getMethod('nestedDotPaths');
});

it('returns only nested keys in dot notation', function (): void {
    $fields = [
        'id',
        'name',
        'author'   => ['id'],
        'comments' => [
            'id',
            'likes' => ['id'],
        ],
    ];

    $result = $this->method->invoke($this->builder, $fields);

    expect($result)->toEqual(['author', 'comments', 'comments.likes']);
});

it('returns empty array for empty fields', function (): void {
    $fields = [];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual([]);
});

it('returns empty array for flat fields', function (): void {
    $fields = ['id', 'name', 'email'];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual([]);
});

it('handles deeply nested fields', function (): void {
    $fields = [
        'a' => [
            'b' => [
                'c' => [
                    'd' => ['id'],
                ],
            ],
        ],
    ];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual(['a', 'a.b', 'a.b.c', 'a.b.c.d']);
});

it('handles numeric keys in nested arrays', function (): void {
    $fields = [
        'users' => [
            0 => ['id'],
            1 => ['name'],
        ],
    ];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual(['users', 'users.0', 'users.1']);
});

it('handles mixed flat and nested fields', function (): void {
    $fields = [
        'id',
        'profile' => [
            'email',
            'address' => ['city'],
        ],
        'settings' => ['theme'],
    ];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual(['profile', 'profile.address', 'settings']);
});

it('handles single nested field', function (): void {
    $fields = [
        'meta' => ['created_at'],
    ];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual(['meta']);
});
