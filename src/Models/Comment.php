<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Models;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model implements CommentContract
{
    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'author_id',
        'author_type',
        'parent_id',
        'text',
        'payload'
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->author_id && auth()->check()) {
                $model->author_id = auth()->id();
                $model->author_type = auth()->user()->getMorphClass();
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CommentableConfig::commentModel(), 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommentableConfig::commentModel(), 'parent_id')
            ->with(['replies', 'author'])
            ->orderBy('created_at');
    }

    public function isAuthor(CommenterContract $author): bool
    {
        return $this->author_id === $author->getKey()
            && $this->author_type === $author->getMorphClass();
    }
}
