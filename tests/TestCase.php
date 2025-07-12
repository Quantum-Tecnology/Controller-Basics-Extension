<?php

namespace QuantumTecnology\ControllerBasicsExtension\Tests;

use Illuminate\Foundation\Console\AboutCommand;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static $migration;
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function setUpDatabase($app): void {
        $schema = $app['db']->connection()->getSchemaBuilder();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (method_exists(AboutCommand::class, 'flushState')) {
            AboutCommand::flushState();
        }
    }
}
