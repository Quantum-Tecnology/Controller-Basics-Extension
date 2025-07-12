<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

test('test', function () {
    $post = Post::factory()->create();

    getJson(route('posts.index'))
        ->assertOk();
});
