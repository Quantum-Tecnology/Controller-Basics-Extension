<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use QuantumTecnology\ControllerBasicsExtension\Support\LogSupport;

beforeEach(function () {
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    Route::middleware(['web', QuantumTecnology\ControllerBasicsExtension\Middleware\LogMiddleware::class])
        ->get('/test-middleware', function () {
            LogSupport::add('Test log message');
            DB::table('posts')->get();

            return response()->json(['foo' => 'bar']);
        });
});

test('middleware adds logs and query log to json response', function () {
    $response = $this->getJson('/test-middleware?enable_query_log=1');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'query_log',
        ]);
});

test('middleware without query log', function () {
    $response = $this->getJson('/test-middleware');

    $response->assertStatus(200)
        ->assertJsonMissing(['query_log']);
});
