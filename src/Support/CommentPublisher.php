<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Support;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommentPublisherContract;
use DissNik\MoonShineCommentable\DTOs\CommentData;

final readonly class CommentPublisher
{
    public function __construct(
        private CommentPublisherContract $publisher,
    ) {}

    public function publishCreated(CommentContract $comment): void
    {
        $this->publisher->publish(
            CommentData::fromComment(
                comment: $comment,
                signal: CommentableConfig::transportCreatedSignal(),
                transport: CommentableConfig::transportMode(),
                version: CommentableConfig::transportPayloadVersion(),
            ),
        );
    }
}
