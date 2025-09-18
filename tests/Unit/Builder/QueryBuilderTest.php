<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(fn () => $this->builder = new QueryBuilder());

describe('Testing together some certain methods', function () {
    beforeEach(function () {
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
            'filter(body)'                   => '2',
            'filter_comments(id)'            => 3,
            'filter_comments(body)'          => '4',
            'filter_comments(title,~)'       => 'testing',
            'filter_comments(id,>=)'         => 2,
            'filter_comments_likes(id)'      => 10,
        ];
    });

    it('extractOptions returns correct nested options array', function () {
        $method = $this->refClass->getMethod('extractOptions');
        $method->setAccessible(true);

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

    it('extractOptions returns correct nested order options array', function () {
        $method = $this->refClass->getMethod('extractOptions');
        $method->setAccessible(true);

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

    it('extractFilters returns correct nested filters array', function () {
        $method = $this->refClass->getMethod('extractFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->instance, $this->dataOptions, new Post(), 'filter');

        expect($result)->toBe([
            Post::class => [
                'id' => [
                    [
                        'operation' => '=',
                        'value'     => 1,
                    ],
                ],
                'body' => [
                    [
                        'operation' => '=',
                        'value'     => '2',
                    ],
                ],
            ],
            'comments' => [
                'id' => [
                    [
                        'operation' => '=',
                        'value'     => 3,
                    ],
                    [
                        'operation' => '>=',
                        'value'     => 2,
                    ],
                ],
                'body' => [
                    [
                        'operation' => '=',
                        'value'     => '4',
                    ],
                ],
                'title' => [
                    [
                        'operation' => '~',
                        'value'     => 'testing',
                    ],
                ],
            ],
            'comments_likes' => [
                'id' => [
                    [
                        'operation' => '=',
                        'value'     => 10,
                    ],
                ],
            ],
        ]);
    });

    it('extractFilters handles field names with "by" prefix as operation', function () {
        $method = $this->refClass->getMethod('extractFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->instance, [
            'filter(byId)'          => 1,
            'filter_comments(byId)' => 2,
        ], new Post(), 'filter');

        expect($result)->toBe([
            Post::class => [
                'byId' => [
                    [
                        'operation' => 'by',
                        'value'     => 1,
                    ],
                ],
            ],
            'comments' => [
                'byId' => [
                    [
                        'operation' => 'by',
                        'value'     => 2,
                    ],
                ],
            ],
        ]);
    });
});
