<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Console\AboutCommand;
use Orchestra\Testbench\TestCase as BaseTestCase;
use QuantumTecnology\ControllerBasicsExtension\Middleware\LogMiddleware;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller\PostController;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        $this->setUpRoute($this->app);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (method_exists(AboutCommand::class, 'flushState')) {
            AboutCommand::flushState();
        }
    }

    protected function setUpDatabase($app): void
    {
        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('author_id')->constrained('authors');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('post_id')->constrained('posts');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('comment_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('comment_id')->constrained('comments');
            $table->unsignedTinyInteger('like');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('post_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('post_id')->constrained('posts');
            $table->unsignedTinyInteger('like');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('post_tag', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags');
            $table->foreignId('post_id')->constrained('posts');
        });

        $schema->create('comment_tag', function (Blueprint $table) {
            $table->foreignId('comment_id')->constrained('comments');
            $table->foreignId('tag_id')->constrained('tags');
        });

        $schema->create('media', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('media_able');
            $table->string('name');
        });
    }

    protected function setUpRoute($app): void
    {
        $app['router']->withoutMiddleware([LogMiddleware::class])->apiResource('posts', PostController::class);
    }
}
