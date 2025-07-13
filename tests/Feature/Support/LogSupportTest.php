<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

beforeEach(function () {
    // Reset static property for isolation
    $ref  = new ReflectionClass(LogSupport::class);
    $prop = $ref->getProperty('messages');
    $prop->setAccessible(true);
    $prop->setValue([]);
});

test('adds a message when app.debug is true', function () {
    config(['app.debug' => true]);
    LogSupport::add('Test message');
    $messages = LogSupport::getMessages();
    expect($messages)->toHaveCount(1);
    expect($messages[0]['message'])->toBe('Test message');
});

test('does not add a message when app.debug is false', function () {
    config(['app.debug' => false]);
    LogSupport::add('Should not log');
    $messages = LogSupport::getMessages();
    expect($messages)->toBe([]);
});

test('getMessages returns all added messages', function () {
    config(['app.debug' => true]);
    LogSupport::add('First');
    LogSupport::add('Second');
    $messages = LogSupport::getMessages();
    expect($messages)->toHaveCount(2);
    expect($messages[0])->toHaveKeys(['message', 'stack_trace']);
    expect($messages[1])->toHaveKeys(['message', 'stack_trace']);
});

test('messages include stack trace with at least 2 frames', function () {
    config(['app.debug' => true]);
    LogSupport::add('Stack trace test');
    $messages = LogSupport::getMessages();
    expect($messages[0]['stack_trace'])->toBeArray();
    expect(count($messages[0]['stack_trace']))->toBeGreaterThanOrEqual(2);
});

test('messages are keyed uniquely by message and line', function () {
    config(['app.debug' => true]);
    LogSupport::add('Same message');
    LogSupport::add('Same message'); // Should be different key due to line number
    $messages = LogSupport::getMessages();
    expect($messages)->toHaveCount(2);
});

test('getMessages returns empty array if nothing added', function () {
    $messages = LogSupport::getMessages();
    expect($messages)->toBe([]);
});
