<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Model $author
 */
interface CommentContract
{
    public function commentable(): MorphTo;

    public function author(): MorphTo;

    public function parent(): BelongsTo;

    public function replies(): HasMany;

    public function isAuthor(CommenterContract $author): bool;
}
