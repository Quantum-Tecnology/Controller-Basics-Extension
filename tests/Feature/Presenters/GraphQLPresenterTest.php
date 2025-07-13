<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(fn () => $this->presenter = app(GraphQLPresenter::class));

test('a', function () {
    $model = Post::factory()->create();

    $fields = ['id', 'title', 'can_delete', 'can_update'];

    $response = $this->presenter->execute($model, $fields);

    expect($response)->toBe([
        'data' => [
            'id'    => $model->id,
            'title' => $model->title,
        ],
        'meta' => [
            'can_delete' => $model->can_delete,
            'can_update' => $model->can_update,
        ],
    ]);
});

test('b', function () {
    $model = Post::factory()->create();

    $fields = ['id', 'title'];

    $response = $this->presenter->execute($model, $fields);

    expect($response)->toBe([
        'data' => [
            'id'    => $model->id,
            'title' => $model->title,
        ],
    ]);
});

test('8', function () {
    $model = Post::factory()->create();

    $fields = ['*'];

    $response = $this->presenter->execute($model, $fields);

    expect($response)->toBe([
        'data' => [
            'id'         => $model->id,
            'title'      => $model->title,
            'created_at' => $model->created_at->toDateTimeString(),
            'updated_at' => $model->updated_at->toDateTimeString(),
            'deleted_at' => null,
        ],
        'meta' => [
            'can_delete' => $model->can_delete,
            'can_update' => $model->can_update,
        ],
    ]);
});
