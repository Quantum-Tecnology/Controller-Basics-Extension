<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function () {
    $this->presenter = app(GraphQLPresenter::class);
    $this->model     = Post::factory()->create();
});

// test('a', function () {
//    $model = Post::factory()->create();
//
//    $fields = ['id', 'title', 'can_delete', 'can_update'];
//
//    $response = $this->presenter->execute($model, $fields);
//
//    expect($response)->toBe([
//        'data' => [
//            'id'    => $model->id,
//            'title' => $model->title,
//        ],
//        'meta' => [
//            'can_delete' => $model->can_delete,
//            'can_update' => $model->can_update,
//        ],
//    ]);
// });
//
// test('b', function () {
//    $model = Post::factory()->create();
//
//    $fields = ['id', 'title'];
//
//    $response = $this->presenter->execute($model, $fields);
//
//    expect($response)->toBe([
//        'data' => [
//            'id'    => $model->id,
//            'title' => $model->title,
//        ],
//    ]);
// });
//
// test('8', function () {
//    $model = Post::factory()->create();
//
//    $fields = ['*'];
//
//    $response = $this->presenter->execute($model, $fields);
//
//    expect($response)->toBe([
//        'data' => [
//            'author_id'   => $model->author_id,
//            'title'       => $model->title,
//            'created_at'  => $model->created_at->toDateTimeString(),
//            'updated_at'  => $model->updated_at->toDateTimeString(),
//            'id'          => $model->id,
//            'use_factory' => null,
//        ],
//        'meta' => [
//            'can_delete' => $model->can_delete,
//            'can_update' => $model->can_update,
//        ],
//    ]);
// });

test('returns only requested fields in data', function () {
    $fields = ['id', 'title'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result)->toBe([
        'data' => [
            'id'    => $this->model->id,
            'title' => $this->model->title,
        ],
    ]);
});

test('fields starting with can_ go to meta', function () {
    $fields = ['id', 'can_delete', 'can_update'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result)->toBe([
        'data' => ['id' => $this->model->id],
        'meta' => [
            'can_delete' => true,
            'can_update' => false,
        ],
    ]);
});

test('date fields are formatted', function () {
    $fields = ['created_at'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result)->toBe([
        'data' => ['created_at' => $this->model->created_at->toDateTimeString()],
    ]);
});

test('asterisk returns all fields and accessors', function () {
    $fields = ['*'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result['data'])->toMatchArray([
        'id'         => $this->model->id,
        'author_id'  => $this->model->author_id,
        'title'      => $this->model->title,
        'created_at' => $this->model->created_at->toDateTimeString(),
        'updated_at' => $this->model->created_at->toDateTimeString(),
    ]);
    expect($result['meta'])->toMatchArray([
        'can_delete' => true,
        'can_update' => false,
    ]);
});

test('meta omitted if empty', function () {
    $fields = ['id', 'title'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result)->not->toHaveKey('meta');
});

test('includes accessors', function () {
    $fields = ['custom', 'custom_2'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result['data'])
        ->toHaveKey('custom', 'custom_value')
        ->and($result['data'])->toHaveKey('custom_2', null);
});

test('includes mutated attributes', function () {
    $fields = ['can_update'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result['meta'])->toHaveKey('can_update', false);
});

test('handles empty fields array', function () {
    $fields = [];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result['data'])
        ->toBe([])
        ->and($result)->not->toHaveKey('meta');
});

test('handles non existent fields gracefully', function () {
    $fields = ['id', 'not_a_field'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result['data'])
        ->toHaveKey('id', 1)
        ->and($result['data'])->toHaveKey('not_a_field', null);
});
