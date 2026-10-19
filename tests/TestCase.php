<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests;

use DissNik\MoonShineCommentable\Providers\MoonShineCommentableServiceProvider;
use Mockery;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected bool $requiresDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (! $this->requiresDatabase) {
            return;
        }
        $connection = $this->app['db']->connection();
        $actual = $connection->selectOne('SELECT current_database() AS name, current_user AS role');
        if ($connection->getDriverName() !== 'pgsql' || $actual->name !== PostgresDatabase::name() || $actual->role !== 'domo_testing') {
            throw new \RuntimeException('Refusing writes outside the isolated PostgreSQL test database.');
        }
        $connection->statement('DROP SCHEMA public CASCADE');
        $connection->statement('CREATE SCHEMA public AUTHORIZATION domo_testing');
    }

    protected function tearDown(): void
    {
        try {
            if ($this->app !== null && $this->requiresDatabase) {
                $this->app['db']->disconnect('testing');
            }
        } finally {
            parent::tearDown();
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            MoonShineCommentableServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $core = Mockery::mock(CoreContract::class);
        $core->shouldReceive('resources')->andReturnSelf();

        $app->instance(CoreContract::class, $core);
        if (! $this->requiresDatabase) {
            $app['config']->set('database.default', 'unit-database-disabled');
            $app['config']->set('database.connections', []);

            return;
        }

        PostgresDatabase::create();
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => PostgresDatabase::port(),
            'database' => PostgresDatabase::name(),
            'username' => 'domo_testing',
            'password' => getenv('DOMO_TEST_PG_PASSWORD') ?: '',
            'charset' => 'utf8',
            'search_path' => 'public',
            'sslmode' => 'prefer',
            'prefix' => '',
        ]);
    }
}
