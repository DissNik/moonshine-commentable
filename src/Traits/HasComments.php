<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Traits;

use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Events\CommentCreatedEvent;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use DissNik\MoonShineCommentable\Support\CommentPublisher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(CommentableConfig::commentModel(), 'commentable');
    }

    public function commentReads(): MorphMany
    {
        return $this->morphMany(CommentableConfig::commentReadModel(), 'commentable');
    }

    public function comment(
        string $text,
        CommenterContract $author,
        CommentContract|int|string|null $parent = null,
        ?array $payload = null,
    ): CommentContract {
        $commentModel = CommentableConfig::commentModel();

        if (! $author->can('create', $commentModel)) {
            throw new AuthorizationException('Cannot create comment');
        }

        if (! CommentableConfig::authorizeCommentable($this, 'create', $author)) {
            throw new AuthorizationException('Cannot create comment for this resource');
        }

        $parentComment = $this->resolveParentComment($parent);

        if (
            $parentComment !== null
            && ! CommentableConfig::authorizeCommentable($this, 'reply', $author, $parentComment)
        ) {
            throw new AuthorizationException('Cannot reply to this comment');
        }

        /** @var CommentContract $comment */
        $comment = $this->comments()->create([
            'parent_id' => $parentComment?->getKey(),
            'text' => $text,
            'payload' => $payload,
            'author_id' => $author->getKey(),
            'author_type' => $author->getMorphClass(),
        ]);

        CommentCreatedEvent::dispatch($comment);
        app(CommentPublisher::class)->publishCreated($comment);

        return $comment;
    }

    public function markCommentsAsRead(CommenterContract $reader, Carbon|string|null $readAt = null): Model
    {
        return $this->commentReads()->updateOrCreate(
            $this->commentReadAttributes($reader),
            ['last_read_at' => $readAt instanceof Carbon ? $readAt : Carbon::parse($readAt ?? 'now')],
        );
    }

    public function hasUnreadComments(CommenterContract $reader): bool
    {
        $loadedUnreadState = $this->getAttribute('has_unread_comments');

        if ($loadedUnreadState !== null) {
            return (bool) $loadedUnreadState;
        }

        return $this->unreadCommentsQuery($reader)->exists();
    }

    public function unreadCommentsCount(CommenterContract $reader): int
    {
        $loadedUnreadCount = $this->getAttribute('unread_comments_count');

        if ($loadedUnreadCount !== null) {
            return (int) $loadedUnreadCount;
        }

        return $this->unreadCommentsQuery($reader)->count();
    }

    public function scopeWithUnreadCommentsState(
        Builder $query,
        CommenterContract $reader,
        string $column = 'has_unread_comments',
    ): Builder {
        $commentModel = CommentableConfig::commentModel();
        $commentTable = (new $commentModel())->getTable();

        return $query->withExists([
            "comments as {$column}" => fn (Builder $commentQuery): Builder => $this->applyUnreadConstraints(
                $commentQuery,
                $reader,
                $commentTable,
            ),
        ]);
    }

    public function scopeWithUnreadCommentsCount(
        Builder $query,
        CommenterContract $reader,
        string $column = 'unread_comments_count',
    ): Builder {
        $commentModel = CommentableConfig::commentModel();
        $commentTable = (new $commentModel())->getTable();

        return $query->withCount([
            "comments as {$column}" => fn (Builder $commentQuery): Builder => $this->applyUnreadConstraints(
                $commentQuery,
                $reader,
                $commentTable,
            ),
        ]);
    }

    protected function unreadCommentsQuery(CommenterContract $reader): Builder
    {
        return $this->applyUnreadConstraints(
            $this->comments()->getQuery(),
            $reader,
            $this->comments()->getRelated()->getTable(),
        );
    }

    protected function applyUnreadConstraints(Builder $query, CommenterContract $reader, string $commentTable): Builder
    {
        $readModel = CommentableConfig::commentReadModel();
        $readTable = (new $readModel())->getTable();
        $readerKey = $reader->getKey();
        $readerType = $reader->getMorphClass();

        return $query
            ->where(function (Builder $authorQuery) use ($commentTable, $readerKey, $readerType): void {
                $authorQuery
                    ->where("{$commentTable}.author_id", '!=', $readerKey)
                    ->orWhere("{$commentTable}.author_type", '!=', $readerType);
            })
            ->where(function (Builder $unreadQuery) use ($commentTable, $readTable, $readerKey, $readerType): void {
                $unreadQuery
                    ->whereNotExists(function ($subQuery) use ($commentTable, $readTable, $readerKey, $readerType): void {
                        $subQuery
                            ->selectRaw('1')
                            ->from($readTable)
                            ->whereColumn("{$readTable}.commentable_id", "{$commentTable}.commentable_id")
                            ->whereColumn("{$readTable}.commentable_type", "{$commentTable}.commentable_type")
                            ->where("{$readTable}.reader_id", $readerKey)
                            ->where("{$readTable}.reader_type", $readerType);
                    })
                    ->orWhere(
                        "{$commentTable}.created_at",
                        '>',
                        function ($subQuery) use ($commentTable, $readTable, $readerKey, $readerType): void {
                            $subQuery
                                ->select("{$readTable}.last_read_at")
                                ->from($readTable)
                                ->whereColumn("{$readTable}.commentable_id", "{$commentTable}.commentable_id")
                                ->whereColumn("{$readTable}.commentable_type", "{$commentTable}.commentable_type")
                                ->where("{$readTable}.reader_id", $readerKey)
                                ->where("{$readTable}.reader_type", $readerType)
                                ->limit(1);
                        },
                    );
            });
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function commentReadAttributes(CommenterContract $reader): array
    {
        return [
            'reader_id' => $reader->getKey(),
            'reader_type' => $reader->getMorphClass(),
        ];
    }

    protected function resolveParentComment(CommentContract|int|string|null $parent): ?CommentContract
    {
        if ($parent === null || $parent === '') {
            return null;
        }

        if ($parent instanceof CommentContract) {
            $parentComment = $parent;
        } else {
            $commentModel = CommentableConfig::commentModel();
            $parentComment = $commentModel::query()->find($parent);
        }

        if (! $parentComment instanceof CommentContract) {
            throw ValidationException::withMessages([
                'parent_id' => __('validation.exists', ['attribute' => 'parent_id']),
            ]);
        }

        if (
            data_get($parentComment, 'commentable_id') !== $this->getKey()
            || data_get($parentComment, 'commentable_type') !== $this->getMorphClass()
        ) {
            throw ValidationException::withMessages([
                'parent_id' => __('validation.exists', ['attribute' => 'parent_id']),
            ]);
        }

        return $parentComment;
    }
}
