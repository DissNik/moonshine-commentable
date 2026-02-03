<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CommentableContract
{
    public function comments(): MorphMany;

    public function comment(CommentableContract $commentable, null|int|string $parent_id, string $text, CommenterContract $author): CommentContract;
}
