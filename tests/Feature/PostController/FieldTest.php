<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

test('it returns a list of posts', function () {
    Post::factory()->create();

    getJson(route('posts.index'))
        ->assertJson([
            'data' => [
                [],
            ],
        ])
        ->assertOk();
});

test('it returns a list of posts with selected author fields', function () {
    $author = Author::factory()->create();
    Post::factory()->for($author)->create();

    getJson(route('posts.index', [
        'fields' => 'author[id,name]',
    ]))
        ->assertJson([
            'data' => [
                [
                    'author' => [
                        'id'   => $author->id,
                        'name' => $author->name,
                    ],
                ],
            ],
        ])
        ->assertOk();
});

test('a', function () {
    Post::factory()->create();

    config(['app.debug' => false]);

    getJson(route('posts.index', [
        'fields' => 'author[*]',
    ]))
        ->dump();
});
