<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;

interface CommentPublisherContract
{
    public function publish(CommentPayloadContract $payload): void;
}
