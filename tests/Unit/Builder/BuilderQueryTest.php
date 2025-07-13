<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;

beforeEach(function () {
    $this->builder = new BuilderQuery();
    $reflection    = new ReflectionClass($this->builder);
    $this->method  = $reflection->getMethod('nestedDotPaths');
    $this->method->setAccessible(true);
});

it('returns only nested keys in dot notation', function () {
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

it('returns empty array for empty fields', function () {
    $fields = [];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual([]);
});

it('returns empty array for flat fields', function () {
    $fields = ['id', 'name', 'email'];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual([]);
});

it('handles deeply nested fields', function () {
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

it('handles numeric keys in nested arrays', function () {
    $fields = [
        'users' => [
            0 => ['id'],
            1 => ['name'],
        ],
    ];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual(['users', 'users.0', 'users.1']);
});

it('handles mixed flat and nested fields', function () {
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

it('handles single nested field', function () {
    $fields = [
        'meta' => ['created_at'],
    ];
    $result = $this->method->invoke($this->builder, $fields);
    expect($result)->toEqual(['meta']);
});
