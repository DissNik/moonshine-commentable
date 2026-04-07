<?php

namespace DissNik\MoonShineCommentable\Policies;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Support\CommentableConfig;

class CommentPolicy
{
    public function viewAny(CommenterContract $user): bool
    {
        return true;
    }

    public function view(CommenterContract $user, CommentContract $comment): bool
    {
        return CommentableConfig::authorizeCommentable(
            $comment->commentable,
            'view',
            $user,
            $comment,
        );
    }

    public function create(CommenterContract $user): bool
    {
        return true;
    }

    public function update(CommenterContract $user, CommentContract $comment): bool
    {
        return $comment->isAuthor($user)
            && CommentableConfig::authorizeCommentable($comment->commentable, 'update', $user, $comment);
    }

    public function reply(CommenterContract $user, CommentContract $comment): bool
    {
        return CommentableConfig::authorizeCommentable($comment->commentable, 'reply', $user, $comment);
    }

    public function delete(CommenterContract $user, CommentContract $comment): bool
    {
        return $comment->isAuthor($user)
            && CommentableConfig::authorizeCommentable($comment->commentable, 'delete', $user, $comment);
    }
}
