<?php

declare(strict_types = 1);

use function Pest\Laravel\getJson;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Comment;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\CommentLike;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

test('it returns paginated posts with correct meta', function (): void {
    $post    = Post::factory()->create();
    $comment = Comment::factory()->for($post)->create();
    CommentLike::factory()->for($comment)->create();

    $response = getJson(route('posts-only-fields.index', [
        'fields' => 'id title author_id author { id } comments {id created_at likes {id created_at} }',
    ]))
        ->assertOk()
        ->assertJson([
            'meta' => [
                'per_page'  => 10,
                'last_page' => 1,
            ],
        ]);

    expect(count($response->json('quantum_log')))->toBe(4)
        ->and($response->json('allowed_fields'))->toBe([
            'id',
            'title',
            'comments' => [
                'id',
                'likes' => [
                    'id',
                ],
            ],
        ])
        ->and($response->json('quantum_log.0.message'))->toBe("Field 'author_id' is not allowed in the request.")
        ->and($response->json('quantum_log.1.message'))->toBe("Field 'author' is not allowed in the request.")
        ->and($response->json('quantum_log.2.message'))->toBe("Field 'comments.created_at' is not allowed in the request.")
        ->and($response->json('quantum_log.3.message'))->toBe("Field 'comments.likes.created_at' is not allowed in the request.");
});

it('does not expose allowed_fields in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $response = getJson(route('posts-only-fields.index', [
        'fields' => 'id title author_id author { id } comments {id created_at likes {id created_at} }',
    ]));

    expect($response->json('allowed_fields'))->toBeNull();
});
