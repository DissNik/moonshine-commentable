<?php

namespace DissNik\MoonShineCommentable\Events;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreatedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CommentContract $comment,
    ) {}
}
