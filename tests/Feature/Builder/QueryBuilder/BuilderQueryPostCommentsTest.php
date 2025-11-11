<?php

declare(strict_types = 1);

use QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

beforeEach(function (): void {
    $this->post    = Post::factory()->create(['is_draft' => true]);
    $this->comment = Comment::factory(25)->for($this->post)->create();

    $this->builder = app(QueryBuilder::class);
});

test('it returns paginated comments for post', function (): void {
    $fields = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $post   = $this->builder->execute($this->post, $fields)->where('id', $this->post->id)->sole();
    expect($post->comments)->toHaveCount(10);
});

test('it paginates comments with per_page parameter', function (): void {
    $fields = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];

    $options = [
        'page_limit_comments' => 5,
    ];

    $post = $this->builder->execute($this->post, $fields, $options)->where('id', $this->post->id)->sole();
    expect($post->comments)->toHaveCount(5);
});

test('it paginates comments with page parameter', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $options = [
        'page_offset_comments' => 2,
    ];

    $post = $this->builder->execute($this->post, $fields, $options)->where('id', $this->post->id)->sole();
    expect($post->comments->get(0)->id)->toBe(3);
});

test('it filters comments by id less than or equal to 3', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'filter_comments(id,<=)' => 20,
    ];

    $post = $this->builder->execute($this->post, $fields, $filters)->where('id', $this->post->id)->sole();
    expect($post)->comments->toHaveCount(10)
        ->comments_count->toBe(20);
});

test('it filters comments by id less than or equal 3', function (): void {
    $fields  = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $filters = [
        'filter_comments(id)' => 3,
    ];

    $post = $this->builder->execute($this->post, $fields, $filters)->where('id', $this->post->id)->sole();
    expect($post->comments)->toHaveCount(1);
});

test('it filters posts by title using byFilter', function (): void {
    $fields  = ['id'];
    $filters = [
        'filter(by_filter,title|body)' => 'testing',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(0);

    Post::factory()->create(['title' => 'testing_' . date('YmdHis')]);
    Post::factory()->create(['title' => 'catatau_' . date('YmdHis')]);

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(1);

    $filters = [
        'filter(by_filter,title|body)' => 'catatau',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(1);

    $filters = [
        'filter(by_filter,title|body)' => 'testing|catatau',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(2);
});

test('it filters posts by is_draft status', function (): void {
    $fields  = ['id'];
    $filters = [
        'filter(is_draft)' => 'true',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(1);

    $filters = [
        'filter(is_draft)' => 'false',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(0);

});

test('it filters posts by is_draft null', function (): void {
    $fields  = ['id'];
    $filters = [
        'filter(is_draft,null)' => 'true',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(0);

    $filters = [
        'filter(is_draft,null)' => 'false',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(1);

});

test('it filters posts by is_draft not null', function (): void {
    $fields  = ['id'];
    $filters = [
        'filter(is_draft,not-null)' => 'true',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(1);

    $filters = [
        'filter(is_draft,not-null)' => 'false',
    ];

    $posts = $this->builder->execute($this->post, $fields, $filters)->get();
    expect($posts)->toHaveCount(0);

});
