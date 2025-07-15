<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function (): void {
    $this->presenter = app(GraphQLPresenter::class);
    $this->post      = Post::factory()->create();
    LogSupport::reset();
});

test('returns only requested fields in data', function (): void {
    $fields = ['id', 'title'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => [
            'id'    => $this->post->id,
            'title' => $this->post->title,
        ],
    ]);
});

test('fields starting with can_ go to meta', function (): void {
    $fields = ['id', 'can_delete', 'can_update'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data'    => ['id' => $this->post->id],
        'actions' => [
            'can_delete' => true,
            'can_update' => false,
        ],
    ]);
});

test('date fields are formatted', function (): void {
    $fields = ['created_at'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->toBe([
        'data' => ['created_at' => $this->post->created_at->toDateTimeString()],
    ]);
});

test('asterisk returns all fields and accessors', function (): void {
    $fields = ['*'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result['data'])
        ->toMatchArray([
            'id'         => $this->post->id,
            'author_id'  => $this->post->author_id,
            'title'      => $this->post->title,
            'created_at' => $this->post->created_at->toDateTimeString(),
            'updated_at' => $this->post->created_at->toDateTimeString(),
        ])
        ->and($result['actions'])->toMatchArray([
            'can_delete' => true,
            'can_update' => false,
        ]);
});

test('meta omitted if empty', function (): void {
    $fields = ['id', 'title'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)->not->toHaveKey('meta');
});

test('includes accessors', function (): void {
    $fields = ['custom', 'custom_old'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result['data'])
        ->toHaveKey('custom', 'custom_value')
        ->and($result['data'])->toHaveKey('custom_old', 'custom_old');
});

test('includes mutated attributes', function (): void {
    $fields = ['can_update'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result['actions'])->toHaveKey('can_update', false);
});

test('handles empty fields array', function (): void {
    $fields = [];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result)
        ->not->toHaveKey('data')
        ->not->toHaveKey('meta');
});

test('handles non existent fields gracefully', function (): void {
    $fields = ['id', 'not_a_field'];
    $result = $this->presenter->execute($this->post, $fields);
    expect($result['data'])
        ->toHaveKey('id', 1)
        ->and($result['data'])->not->toHaveKey('not_a_field', null)
        ->and(LogSupport::getMessages())->toHaveCount(1);
});
