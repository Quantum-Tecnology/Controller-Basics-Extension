<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;

beforeEach(fn () => $this->builder = new QueryBuilder());

describe('Testing together some certain methods', function () {
    beforeEach(function () {
        $this->refClass = new ReflectionClass(QueryBuilder::class);
        $this->instance = $this->refClass->newInstanceWithoutConstructor();
    });

    it('generateIncludes returns correct includes and closures for nested relations', function () {
        $method = $this->refClass->getMethod('extractOptions', 'page_offset', 'page_limit');
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
        ]);

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
});
