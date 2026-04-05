<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;

interface CommentPayloadContract
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
