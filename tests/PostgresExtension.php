<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class PostgresExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        PostgresDatabase::create();
        fwrite(STDERR, 'Isolated PostgreSQL database: '.PostgresDatabase::name()."\n");
        $facade->registerSubscriber(new class implements ExecutionFinishedSubscriber
        {
            public function notify(ExecutionFinished $event): void
            {
                PostgresDatabase::drop();
            }
        });
    }
}
