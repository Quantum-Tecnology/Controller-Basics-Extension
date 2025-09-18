<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\Support\FieldParser;
use QuantumTecnology\ControllerBasicsExtension\Builder\Support\FilterParser;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(function () {
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

it('extractFilters returns correct nested filters array', function (): void {
    $result = FilterParser::extract(new Post(), $this->dataOptions);

    expect($result)->toEqual([
        Post::class => [
            'id' => [
                [
                    'operation' => '=',
                    'value'     => collect([1]),
                ],
            ],
            'body' => [
                [
                    'operation' => '=',
                    'value'     => collect(['2']),
                ],
            ],
            'title' => [
                [
                    'operation' => '=',
                    'value'     => collect(['1', '2', '3']),
                ],
            ],
        ],
        'comments' => [
            'id' => [
                [
                    'operation' => '=',
                    'value'     => collect([3]),
                ],
                [
                    'operation' => '>=',
                    'value'     => collect([2]),
                ],
            ],
            'body' => [
                [
                    'operation' => '=',
                    'value'     => collect(['4']),
                ],
            ],
            'title' => [
                [
                    'operation' => '~',
                    'value'     => collect(['testing']),
                ],
            ],
        ],
        'comments_likes' => [
            'id' => [
                [
                    'operation' => '=',
                    'value'     => collect([10]),
                ],
            ],
            'title' => [
                [
                    'operation' => '=',
                    'value'     => collect(['1', '2', '3']),
                ],
            ],
        ],
    ]);
});

it('extractFilters handles field names with "by" prefix as operation', function (): void {
    $result = FilterParser::extract(new Post(), [
        'filter(byId)'          => 1,
        'filter_comments(byId)' => 2,
    ]);

    expect($result)->toEqual([
        Post::class => [
            'byId' => [
                [
                    'operation' => 'by',
                    'value'     => collect([1]),
                ],
            ],
        ],
        'comments' => [
            'byId' => [
                [
                    'operation' => 'by',
                    'value'     => collect([2]),
                ],
            ],
        ],
    ]);
});

it('parses nested and flat field strings with spaces and braces', function (): void {
    $result = FieldParser::normalize('id comments { likes { comment } author { id }');

    expect($result)->toEqual(['id', 'comments' => ['likes' => ['comment' => []]], 'author' => ['id']]);

    $result = FieldParser::normalize('id comments {likes {comment} author {id} tags');

    expect($result)->toEqual(['id', 'comments' => ['likes' => ['comment' => []]], 'author' => ['id'], 'tags']);
});

it('parses simple flat field string', function (): void {
    $result = FieldParser::normalize('id comments');

    expect($result)->toEqual(['id', 'comments']);
});

it('extractFilters handles null and not-null operations', function (): void {
    $result = FilterParser::extract(new Post(), [
        'filter(id,null)'     => null,
        'filter(id,not-null)' => null,
        'filter(title)'       => null,
    ]);

    expect($result)->toEqual([
        Post::class => [
            'id' => [
                [
                    'operation' => 'null',
                    'value'     => null,
                ],
                [
                    'operation' => 'not-null',
                    'value'     => null,
                ],
            ],
        ],
    ]);
});
