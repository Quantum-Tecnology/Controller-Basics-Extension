<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\CommentLike;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

test('it returns post with comments and likes counts', function (): void {
    $post = Post::factory()->create();
    Comment::factory(3)->for($post)->create()->map(function (Comment $comment): void {
        CommentLike::factory(5)->for($comment)->create();
    });

    $response = getJson(route('posts.index', [
        'fields' => 'id title comments {likes{id}}',
    ]))
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'data' => [
                        'comments' => [
                            'data' => [
                                '*' => [
                                    'data' => [
                                        'likes',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->json('data')[0];

    expect($response['data']['comments']['meta']['total'])
        ->toBe(3)
        ->and($response['data']['comments']['data'][0]['data']['likes']['meta']['total'])->toBe(5);
});
