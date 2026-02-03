<?php

namespace DissNik\MoonShineCommentable\Policies;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;

class CommentPolicy
{
    public function create(CommenterContract $user): bool
    {
        return true;
    }

    public function update(CommenterContract $user, CommentContract $comment): bool
    {
        return $comment->isAuthor($user);
    }

    public function reply(CommenterContract $user, CommentContract $comment): bool
    {
        return true;
    }

    public function delete(CommenterContract $user, CommentContract $comment): bool
    {
        return $comment->isAuthor($user);
    }
}
