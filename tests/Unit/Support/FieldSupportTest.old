<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Support\FieldSupport;

it('parses flat fields', function (): void {
    $support = new FieldSupport();
    expect($support->parse('name age email'))->toBe(['name', 'age', 'email']);
});

it('parses nested fields', function (): void {
    $support = new FieldSupport();
    $input   = 'user{name age}';
    expect($support->parse($input))->toBe([
        'user' => ['name', 'age'],
    ]);
});

it('parses nested fields with all fields', function (): void {
    $support = new FieldSupport();
    $input   = 'user{*}';
    expect($support->parse($input))->toBe([
        'user' => ['*'],
    ]);
});

it('parses multiple nested levels', function (): void {
    $support = new FieldSupport();
    $input   = 'user{name address{city zip}}';
    expect($support->parse($input))->toBe([
        'user' => [
            'name',
            'address' => [
                'city',
                'zip',
            ],
        ],
    ]);
});

it('parses multiple top-level and nested fields', function (): void {
    $support = new FieldSupport();
    $input   = 'id user{name age} status';
    expect($support->parse($input))->toBe([
        'id',
        'user' => ['name', 'age'],
        'status',
    ]);
});

it('returns empty array for empty input', function (): void {
    $support = new FieldSupport();
    expect($support->parse(''))->toBe([]);
});

it('handles input with only braces', function (): void {
    $support = new FieldSupport();
    expect($support->parse('{}'))->toBe([]);
});

it('handles input with only braces with spaces', function (): void {
    $support = new FieldSupport();
    expect($support->parse('{                     }'))->toBe([]);
});

it('handles unbalanced braces gracefully', function (): void {
    $support = new FieldSupport();
    expect($support->parse('user{name age'))->toBe([
        'user' => ['name', 'age'],
    ]);
});

it('parses fields with underscores and numbers', function (): void {
    $support = new FieldSupport();
    $input   = 'user_1{field_2 field3}';
    expect($support->parse($input))->toBe([
        'user_1' => ['field_2', 'field3'],
    ]);
});
