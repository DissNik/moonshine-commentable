<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CommentableContract
{
    public function comments(): MorphMany;

    public function commentReads(): MorphMany;

    public function comment(
        string $text,
        CommenterContract $author,
        CommentContract|int|string|null $parent = null,
        ?array $payload = null,
    ): CommentContract;
}
