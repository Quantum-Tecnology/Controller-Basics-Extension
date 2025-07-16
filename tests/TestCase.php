<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Orchestra\Testbench\TestCase as BaseTestCase;
use QuantumTecnology\ControllerBasicsExtension\Providers\ControllerBasicsExtensionProvider;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller\PostCommentController;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller\PostController;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        $this->setUpRoute($this->app);
        Model::unguard();
        $this->app->register(ControllerBasicsExtensionProvider::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (method_exists(AboutCommand::class, 'flushState')) {
            AboutCommand::flushState();
        }
    }

    protected function setUpDatabase(Application $app): void
    {
        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('authors', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('posts', function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('author_id')->constrained('authors');
            $table->string('title');
            $table->json('meta')->nullable();
            $table->unsignedTinyInteger('status');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('comments', function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('post_id')->constrained('posts');
            $table->string('body');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('comment_likes', function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('comment_id')->constrained('comments');
            $table->unsignedTinyInteger('like');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('post_likes', function (Blueprint $table): void {
            $table->increments('id');
            $table->foreignId('post_id')->constrained('posts');
            $table->unsignedTinyInteger('like');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('tags', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('post_tag', function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained('tags');
            $table->foreignId('post_id')->constrained('posts');
        });

        $schema->create('comment_tag', function (Blueprint $table): void {
            $table->foreignId('comment_id')->constrained('comments');
            $table->foreignId('tag_id')->constrained('tags');
        });

        $schema->create('media', function (Blueprint $table): void {
            $table->increments('id');
            $table->morphs('media_able');
            $table->string('name');
        });
    }

    protected function setUpRoute(Application $app): void
    {
        $app['router']->apiResource('posts', PostController::class);
        $app['router']->prefix('post/{post_id}')->group(function () use ($app): void {
            $app['router']->apiResource('comments', PostCommentController::class);
        });
    }
}
