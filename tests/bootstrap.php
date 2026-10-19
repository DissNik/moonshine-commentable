<?php

declare(strict_types=1);
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Core\Core;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/PostgresDatabase.php';
require __DIR__.'/PostgresExtension.php';
require __DIR__.'/TestCase.php';

Core::setInstance(static fn () => app(CoreContract::class));
