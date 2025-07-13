<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function (): void {
    $this->presenter = app(GraphQLPresenter::class);
    $this->author    = Author::factory()->create();
    $this->post      = Post::factory()->for($this->author)->create();
});

test('returns only requested fields in data', function (): void {
    $fields = ['id', 'author_id', 'author' => ['id', 'name']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => [
            'id'        => $this->post->id,
            'author_id' => $this->post->author_id,
            'author'    => [
                'data' => [
                    'id'   => $this->author->id,
                    'name' => $this->author->name,
                ],
            ],
        ],
    ]);
});

test('fields starting with can_ go to meta', function (): void {
    $fields = ['author' => ['id', 'can_delete', 'can_update']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => [
            'author' => [
                'data' => ['id' => $this->author->id],
                'meta' => [
                    'can_delete' => true,
                    'can_update' => false,
                ],
            ],
        ],
    ]);
});

test('asterisk returns all fields and accessors', function (): void {
    $fields = ['author' => ['*']];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result['data']['author'])
        ->toMatchArray([
            'data' => [
                'id'          => $this->author->id,
                'name'        => $this->author->name,
                'created_at'  => $this->author->created_at->toDateTimeString(),
                'updated_at'  => $this->author->created_at->toDateTimeString(),
                'deleted_at'  => null,
                'use_factory' => null,
            ],
            'meta' => [
                'can_delete' => true,
                'can_update' => false,
            ],
        ]);
});
