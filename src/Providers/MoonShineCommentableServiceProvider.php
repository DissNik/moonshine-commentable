<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Providers;

use DissNik\MoonShineCommentable\Contracts\CommentPublisherContract;
use DissNik\MoonShineCommentable\Support\CommentPublisher;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use DissNik\MoonShineCommentable\Support\NullCommentPublisher;
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

        Gate::policy(
            CommentableConfig::commentModel(),
            CommentableConfig::commentPolicy(),
        );

        if (CommentableConfig::registerMoonShineResource()) {
            $core
                ->resources([
                    CommentableConfig::moonShineResource(),
                ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/commentable.php',
            'moonshine-commentable'
        );

        $this->app->singleton(CommentPublisherContract::class, function ($app): CommentPublisherContract {
            $publisher = CommentableConfig::transportPublisher();

            if ($publisher === null) {
                return new NullCommentPublisher;
            }

            return $app->make($publisher);
        });

        $this->app->singleton(CommentPublisher::class);
    }
}
