<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function () {
    $this->presenter = app(GraphQLPresenter::class);
    $this->author    = Author::factory()->create();
    $this->post      = Post::factory()->for($this->author)->create();
});

test('a', function () {
    $fields = ['id', 'author_id', 'author' => ['id', 'name']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => [
            'id'        => $this->post->id,
            'author_id' => $this->post->author_id,
        ],
    ]);
});
