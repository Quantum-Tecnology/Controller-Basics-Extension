<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

beforeEach(function (): void {
    $this->builder = new QueryBuilder();

    $this->refClass = new ReflectionClass(QueryBuilder::class);
    $this->instance = $this->refClass->newInstanceWithoutConstructor();

    $this->dataOptions = [
        'author',
        'comments',
        'comments.likes',
        'comments.likes.comment',
        'comments.likes.comment.likes',
        'page_offset_comments'           => 3,
        'page_limit_comments'            => 25,
        'page_offset_comments_likes'     => 2,
        'page_limit_comments_likes'      => 10,
        'order_column_comments'          => 'id',
        'order_direction_comments'       => 'asc',
        'order_column_comments_likes'    => 'name',
        'order_direction_comments_likes' => 'desc',
        'filter(id)'                     => 1,
        'filter(title)'                  => '1|2|3',
        'filter(body)'                   => '2',
        'filter_comments(id)'            => 3,
        'filter_comments(body)'          => '4',
        'filter_comments(title,~)'       => 'testing',
        'filter_comments(id,>=)'         => 2,
        'filter_comments_likes(id)'      => 10,
        'filter_comments_likes(title)'   => '1|2|3',
    ];
});

it('extractOptions returns correct nested options array', function (): void {
    $method = $this->refClass->getMethod('extractOptions');

    $result = $method->invoke($this->instance, $this->dataOptions, 'page_offset', 'page_limit');

    expect($result)->toBe([
        'comments' => [
            'page_limit'  => 25,
            'page_offset' => 3,
        ],
        'comments_likes' => [
            'page_limit'  => 10,
            'page_offset' => 2,
        ],
    ]);
});

it('extractOptions returns correct nested order options array', function (): void {
    $method = $this->refClass->getMethod('extractOptions');

    $result = $method->invoke($this->instance, $this->dataOptions, 'order_column', 'order_direction');

    expect($result)->toBe([
        'comments' => [
            'order_column'    => 'id',
            'order_direction' => 'asc',
        ],
        'comments_likes' => [
            'order_column'    => 'name',
            'order_direction' => 'desc',
        ],
    ]);
});
