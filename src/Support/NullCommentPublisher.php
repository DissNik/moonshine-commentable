<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Support;

use DissNik\MoonShineCommentable\Contracts\CommentPayloadContract;
use DissNik\MoonShineCommentable\Contracts\CommentPublisherContract;

final class NullCommentPublisher implements CommentPublisherContract
{
    public function publish(CommentPayloadContract $payload): void {}
}
