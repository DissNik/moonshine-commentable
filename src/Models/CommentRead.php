<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentRead extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'reader_id',
        'reader_type',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reader(): MorphTo
    {
        return $this->morphTo();
    }
}
