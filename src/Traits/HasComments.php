<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Traits;

use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Events\CommentCreatedEvent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(config('moonshine-commentable.comment.model'), 'commentable');
    }

    public function commentReads(): MorphMany
    {
        return $this->morphMany(config('moonshine-commentable.comment_read.model'), 'commentable');
    }

    public function comment(CommentableContract $commentable, null|int|string $parent_id, string $text, CommenterContract $author): CommentContract
    {
        $commentModel = config('moonshine-commentable.comment.model');

        if (! $author->can('create', $commentModel)) {
            throw new AuthorizationException('Cannot create comment');
        }

        $comment = $commentable->comments()->create([
            'parent_id' => $parent_id ?: null,
            'text' => $text,
            'author_id' => $author->getKey(),
            'author_type' => $author->getMorphClass(),
        ]);

        CommentCreatedEvent::dispatch($comment);

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
        $commentModel = config('moonshine-commentable.comment.model');
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
        $commentModel = config('moonshine-commentable.comment.model');
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
        $readModel = config('moonshine-commentable.comment_read.model');
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
}
