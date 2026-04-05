<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Support;

use DissNik\MoonShineCommentable\Policies\CommentPolicy;
use DissNik\MoonShineCommentable\Resources\CommentResource;
use DissNik\MoonShineCommentable\Resources\Pages\CommentFormPage;
use DissNik\MoonShineCommentable\Resources\Pages\CommentIndexPage;

final class CommentableConfig
{
    public static function commentModel(): string
    {
        return (string) config('moonshine-commentable.models.comment');
    }

    public static function commentReadModel(): string
    {
        return (string) config('moonshine-commentable.models.comment_read');
    }

    public static function commentPolicy(): string
    {
        return (string) config('moonshine-commentable.policies.comment', CommentPolicy::class);
    }

    public static function registerMoonShineResource(): bool
    {
        return (bool) config('moonshine-commentable.moonshine.register_resource', true);
    }

    public static function moonShineResource(): string
    {
        return (string) config('moonshine-commentable.moonshine.resource', CommentResource::class);
    }

    public static function moonShineIndexPage(): string
    {
        return (string) config('moonshine-commentable.moonshine.pages.index', CommentIndexPage::class);
    }

    public static function moonShineFormPage(): string
    {
        return (string) config('moonshine-commentable.moonshine.pages.form', CommentFormPage::class);
    }

    public static function commentAddedEvent(): string
    {
        return (string) config('moonshine-commentable.moonshine.events.comment_added', 'moonshine-commentable:comment-added');
    }

    public static function commentsHeight(): string
    {
        return (string) config('moonshine-commentable.ui.height', '600px');
    }

    public static function scrollThreshold(): int
    {
        return (int) config('moonshine-commentable.ui.threshold', 200);
    }

    public static function transportMode(): string
    {
        return (string) config('moonshine-commentable.transport.mode', 'polling');
    }

    public static function pollingInterval(): mixed
    {
        return config('moonshine-commentable.transport.polling.interval');
    }

    public static function transportCreatedSignal(): string
    {
        return (string) config('moonshine-commentable.transport.signals.comment_created', 'comment.created');
    }

    public static function transportPayloadVersion(): int
    {
        return (int) config('moonshine-commentable.transport.payload.version', 1);
    }

    public static function transportPublisher(): ?string
    {
        $publisher = config('moonshine-commentable.transport.publisher');

        return is_string($publisher) && $publisher !== '' ? $publisher : null;
    }
}
