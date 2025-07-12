<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\CommentLike;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

test('it returns post with comments and likes counts', function () {
    $post = Post::factory()->create();
    Comment::factory(3)->for($post)->create()->map(function (Comment $comment) {
        CommentLike::factory(5)->for($comment)->create();
    });

    $response = getJson(route('posts.index', [
        'fields' => 'id,title;comments[id];comments.likes[id]',
    ]))
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'comments' => [
                        'data' => [
                            '*' => [
                                'likes' => [
                                    'data',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])
        ->json('data')[0];

    expect($response['comments']['meta']['total_items'])
        ->toBe(3)
        ->and($response['comments']['data'][0]['likes']['meta']['total_items'])->toBe(5);
});
