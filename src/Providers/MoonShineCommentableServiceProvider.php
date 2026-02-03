<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Providers;

use DissNik\MoonShineCommentable\Resources\Comment\CommentResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;

class MoonShineCommentableServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core): void
    {
        $this->publishesMigrations([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'moonshine-commentable-migrations');

        $this->publishes(
            [__DIR__ . '/../../config/commentable.php' => config_path('moonshine-commentable.php')],
            ['moonshine-commentable', 'moonshine-commentable-config', 'laravel-config']
        );

        $this->publishes(
            [__DIR__ . '/../../lang' => $this->app->langPath('vendor/moonshine-commentable')],
            ['moonshine-commentable', 'moonshine-commentable-lang', 'laravel-lang']
        );

        $this->publishes(
            [__DIR__ . '/../../public' => public_path('vendor/moonshine-commentable')],
            ['moonshine-commentable', 'moonshine-commentable-assets', 'laravel-assets']
        );

        $this->loadViewsFrom(
            __DIR__ . '/../../resources/views',
            'moonshine-commentable'
        );

        $this->loadTranslationsFrom(
            __DIR__ . '/../../lang',
            'moonshine-commentable'
        );

        Gate::policy( config('moonshine-commentable.comment.model'), config('moonshine-commentable.comment.policy'));

        $core
            ->resources([
                CommentResource::class,
            ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/commentable.php',
            'moonshine-commentable'
        );
    }
}
