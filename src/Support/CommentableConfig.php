<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Support;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommentableAccessContract;
use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Policies\CommentPolicy;
use DissNik\MoonShineCommentable\Resources\CommentResource;
use DissNik\MoonShineCommentable\Resources\Pages\CommentFormPage;
use DissNik\MoonShineCommentable\Resources\Pages\CommentIndexPage;
use Illuminate\Database\Eloquent\Relations\Relation;

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

    public static function resolveCommentable(null|int|string $id, ?string $type): ?CommentableContract
    {
        if ($id === null || $id === '' || $type === null || $type === '') {
            return null;
        }

        $resolver = config('moonshine-commentable.commentables.resolver');

        if (is_callable($resolver)) {
            $commentable = $resolver($id, $type);

            return $commentable instanceof CommentableContract ? $commentable : null;
        }

        $modelClass = self::resolveCommentableModelClass($type);

        if ($modelClass === null) {
            return null;
        }

        $commentable = $modelClass::query()->find($id);

        return $commentable instanceof CommentableContract ? $commentable : null;
    }

    public static function authorizeCommentable(
        CommentableContract $commentable,
        string $ability,
        ?CommenterContract $actor = null,
        ?CommentContract $comment = null,
    ): bool {
        $callback = config('moonshine-commentable.commentables.authorize');

        if (is_callable($callback)) {
            return (bool) $callback($commentable, $ability, $actor, $comment);
        }

        if ($commentable instanceof CommentableAccessContract) {
            return match ($ability) {
                'view' => $commentable->canViewComments($actor),
                'create' => $actor instanceof CommenterContract && $commentable->canCreateComment($actor),
                'reply' => $actor instanceof CommenterContract
                    && $comment instanceof CommentContract
                    && $commentable->canReplyToComment($actor, $comment),
                'update' => $actor instanceof CommenterContract
                    && $comment instanceof CommentContract
                    && $commentable->canUpdateComment($actor, $comment),
                'delete' => $actor instanceof CommenterContract
                    && $comment instanceof CommentContract
                    && $commentable->canDeleteComment($actor, $comment),
                default => false,
            };
        }

        return true;
    }

    public static function resolveAuthorName(CommentContract $comment): string
    {
        $resolver = config('moonshine-commentable.presenters.author_name');

        if (is_callable($resolver)) {
            return (string) $resolver($comment);
        }

        $author = data_get($comment, 'author');

        if ($author instanceof CommenterContract) {
            return $author->getCommenterName();
        }

        return (string) data_get($author, 'name', '');
    }

    public static function resolveAuthorAvatar(CommentContract $comment): ?string
    {
        $resolver = config('moonshine-commentable.presenters.author_avatar');

        if (is_callable($resolver)) {
            $avatar = $resolver($comment);

            return $avatar === null ? null : (string) $avatar;
        }

        $author = data_get($comment, 'author');

        if ($author instanceof CommenterContract) {
            return $author->getCommenterAvatar();
        }

        $avatar = data_get($author, 'avatar_url');

        return $avatar === null ? null : (string) $avatar;
    }

    public static function resolveCommentableModelClass(string $type): ?string
    {
        $morphedModel = Relation::getMorphedModel($type);
        $modelClass = is_string($morphedModel) && $morphedModel !== '' ? $morphedModel : $type;

        if (! class_exists($modelClass) || ! is_a($modelClass, CommentableContract::class, true)) {
            return null;
        }

        return $modelClass;
    }
}
