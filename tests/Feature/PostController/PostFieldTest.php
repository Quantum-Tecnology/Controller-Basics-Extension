<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

test('it returns a show of posts', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', $p))
        ->assertJson([])
        ->assertOk();
});

test('it returns only the requested fields for a post', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'id title']))
        ->assertJson([
            'data' => [
                'id'    => $p->id,
                'title' => $p->title,
            ],
        ])
        ->assertOk();
});

test('it returns only the requested fields and actions for a post', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'id can_update']))
        ->assertJson([
            'data' => [
                'id' => $p->id,
            ],
            'actions' => [
                'can_update' => false,
            ],
        ])
        ->assertOk();
});

test('it returns only the requested actions for a post', function (): void {
    $p = Post::factory()->create();

    getJson(route('posts.show', ['post' => $p->id, 'fields' => 'can_update']))
        ->assertJson([
            'actions' => [
                'can_update' => false,
            ],
        ])
        ->assertOk();
});
