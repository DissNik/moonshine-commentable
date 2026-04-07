<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;

interface CommentableAccessContract
{
    public function canViewComments(?CommenterContract $actor): bool;

    public function canCreateComment(CommenterContract $actor): bool;

    public function canReplyToComment(CommenterContract $actor, CommentContract $comment): bool;

    public function canUpdateComment(CommenterContract $actor, CommentContract $comment): bool;

    public function canDeleteComment(CommenterContract $actor, CommentContract $comment): bool;
}
