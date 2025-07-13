<?php

declare(strict_types = 1);

use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Builder\BuilderQuery;
use QuantumTecnology\ControllerBasicsExtension\Presenters\GraphQLPresenter;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

beforeEach(function () {
    $this->post    = Post::factory()->create();
    $this->comment = Comment::factory(25)->for($this->post)->create();

    $this->builder = app(BuilderQuery::class);
    //    $this->presenter = app(GraphQLPresenter::class);
});

test('it returns paginated comments for post', function () {
    $fields = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    //    $result = $this->presenter->execute($this->builder->execute($this->post, $fields)->find($this->post->id), $fields);
    //    expect($result['data']['comments']['data'])->toHaveCount(10);
});

test('it paginates comments with per_page parameter', function () {
    $fields   = ['author' => ['id'], 'comments' => ['id', 'likes' => ['id']]];
    $paginate = ['comments' => ['per_page' => 5]];

    /** @var Model $builder */
    $builder = $this->builder->execute($this->post, $fields, $paginate)->find($this->post->id);
    $result  = $this->presenter->execute($builder, $fields);
    //    expect($result['data']['comments']['data'])->toHaveCount(5);
});
