<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

beforeEach(fn () => $this->builder = new QueryBuilder());

describe('Testing together some certain methods', function () {
    beforeEach(function () {
        $this->refClass = new ReflectionClass(QueryBuilder::class);
        $this->instance = $this->refClass->newInstanceWithoutConstructor();
    });

    it('extractOptions returns correct nested options array', function () {
        $method = $this->refClass->getMethod('extractOptions');
        $method->setAccessible(true);

        $result = $method->invoke($this->instance, [
            'author',
            'comments',
            'comments.likes',
            'comments.likes.comment',
            'comments.likes.comment.likes',
            'page_offset_comments'       => 3,
            'page_limit_comments'        => 25,
            'page_offset_comments_likes' => 2,
            'page_limit_comments_likes'  => 10,
        ], 'page_offset', 'page_limit');

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

        $result = $method->invoke($this->instance, [
            'author',
            'comments',
            'comments.likes',
            'comments.likes.comment',
            'comments.likes.comment.likes',
            'order_column_comments'          => 'id',
            'order_direction_comments'       => 'asc',
            'order_column_comments_likes'    => 'name',
            'order_direction_comments_likes' => 'desc',
        ], 'order_column', 'order_direction');

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
});
