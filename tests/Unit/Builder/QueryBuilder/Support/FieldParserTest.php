<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\FieldParser;

beforeEach(function (): void {
    $this->execute = new FieldParser();
});

test('100', function (): void {
    $response = $this->execute->normalize('id title');

    expect($response)->toBe(['id', 'title']);
});

test('200', function (): void {
    $response = $this->execute->normalize('id title comments { id body }');

    expect($response)->toBe(['id', 'title', 'comments' => ['id', 'body']]);

    $response = $this->execute->normalize('id title comments {id body}');

    expect($response)->toBe(['id', 'title', 'comments' => ['id', 'body']]);
});

test('300', function (): void {
    $response = $this->execute->normalize('id title comments { id body } author {id name}');

    expect($response)->toBe(['id', 'title', 'comments' => ['id', 'body'], 'author' => ['id', 'name']]);
});
