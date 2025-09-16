<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Support\OrderSupport;

beforeEach(function (): void {
    $this->support = new OrderSupport();
});

it('parses initial order data', function (): void {
    $data = [
        'order_column'    => 'id',
        'order_direction' => 'desc',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'order' => [
            'column'    => 'id',
            'direction' => 'desc',
        ],
    ]);
});

it('parses order data for items', function (): void {
    $data = [
        'order_column.items'    => 'id',
        'order_direction.items' => 'desc',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'items' => [
            'order' => [
                'column'    => 'id',
                'direction' => 'desc',
            ],
        ],
    ]);
});

it('parses nested order data for children of items', function (): void {
    $data = [
        'order_column_items.comment'    => 'id',
        'order_direction_items.comment' => 'desc',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'items' => [
            'children' => [
                'order' => [
                    'column'    => 'id',
                    'direction' => 'desc',
                ],
            ],
        ],
    ]);
});

it('parses order data for items and their children', function (): void {
    $data = [
        'order_column_items'                       => 'id',
        'order_direction_items'                    => 'desc',
        'order_column_items.comment'               => 'id',
        'order_direction_items.comment'            => 'desc',
        'order_column_items.comment.likes'         => 'id',
        'order_column_items.comment.likes.comment' => 'id',
    ];
    $result = $this->support->parse($data);

    expect($result)->toBe([
        'items' => [
            'order' => [
                'column'    => 'id',
                'direction' => 'desc',
            ],
            'children' => [
                'order' => [
                    'column'    => 'id',
                    'direction' => 'desc',
                    'children'  => [
                        'column'    => 'id',
                        'direction' => 'asc',
                        'children'  => [
                            'column'    => 'id',
                            'direction' => 'asc',
                        ],
                    ],
                ],
            ],
        ],
    ]);
});
