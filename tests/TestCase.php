<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests;

use DissNik\MoonShineCommentable\Providers\MoonShineCommentableServiceProvider;
use Mockery;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
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
    }
}
