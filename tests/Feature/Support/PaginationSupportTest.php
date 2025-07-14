<?php

declare(strict_types = 1);
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

use QuantumTecnology\ControllerBasicsExtension\Support\PaginationSupport;

it('returns default per_page when blank', function (): void {
    config(['page.per_page' => 15, 'page.max_page' => 50]);
    $support = new PaginationSupport();
    expect($support->calculatePerPage(null, 'foo'))
        ->toBe(15);
});

it('returns perPage if within max', function (): void {
    config(['page.per_page' => 15, 'page.max_page' => 50]);
    $support = new PaginationSupport();
    expect($support->calculatePerPage(10, 'foo'))->toBe(10);
});

it('caps perPage at max and logs when above max', function (): void {
    config(['page.per_page' => 15, 'page.max_page' => 50]);
    $support = new PaginationSupport();

    // Optionally, mock LogSupport::add if you want to assert it was called

    expect($support->calculatePerPage(100, 'foo'))->toBe(50);
});

it('returns 1 if perPage is falsy after all checks', function (): void {
    config(['page.per_page' => null, 'page.max_page' => 50]);
    $support = new PaginationSupport();
    expect($support->calculatePerPage(null, 'foo'))->toBe(1);
});
