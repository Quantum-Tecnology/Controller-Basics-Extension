<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function () {
    $this->presenter = app(GraphQLPresenter::class);
    $this->model     = Post::factory()->create();
});

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
    $fields = ['custom', 'custom_old'];
    $result = $this->presenter->execute($this->model, $fields);
    expect($result['data'])
        ->toHaveKey('custom', 'custom_value')
        ->and($result['data'])->toHaveKey('custom_old', 'custom_old');
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
        ->and($result['data'])->toHaveKey('not_a_field', null)
        ->and(LogSupport::getMessages())->toHaveCount(1);
});
