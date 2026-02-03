<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Traits;

use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Events\CommentCreatedEvent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(config('moonshine-commentable.comment.model'), 'commentable');
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
}
