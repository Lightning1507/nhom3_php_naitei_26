<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSafeTestingDatabase();
    }

    private function ensureSafeTestingDatabase(): void
    {
        if (! $this->app->environment('testing')) {
            throw new RuntimeException('The automated test suite may only run in the testing environment.');
        }

        $connection = (string) config('database.default');
        $host = (string) config("database.connections.{$connection}.host");
        $database = (string) config("database.connections.{$connection}.database");
        $allowedHosts = ['127.0.0.1', 'localhost', '::1'];

        if ($connection !== 'pgsql' || ! in_array($host, $allowedHosts, true)) {
            throw new RuntimeException('Tests require an isolated local PostgreSQL connection.');
        }

        if ($database === '' || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('The PostgreSQL database name must end with "_testing".');
        }
    }
}
