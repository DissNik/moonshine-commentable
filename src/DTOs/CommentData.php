<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\DTOs;

use Carbon\CarbonInterface;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommentPayloadContract;

final readonly class CommentData implements CommentPayloadContract
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        private string $signal,
        private int $version,
        private string $transport,
        private null|int|string $commentId,
        private null|int|string $parentId,
        private ?string $text,
        private ?array $payload,
        private null|int|string $commentableId,
        private ?string $commentableType,
        private null|int|string $authorId,
        private ?string $authorType,
        private ?string $createdAt,
        private ?string $updatedAt,
    ) {}

    public static function fromComment(
        CommentContract $comment,
        string $signal,
        string $transport,
        int $version,
    ): self {
        return new self(
            signal: $signal,
            version: $version,
            transport: $transport,
            commentId: data_get($comment, 'id'),
            parentId: data_get($comment, 'parent_id'),
            text: data_get($comment, 'text'),
            payload: self::normalizePayload(data_get($comment, 'payload')),
            commentableId: data_get($comment, 'commentable_id'),
            commentableType: data_get($comment, 'commentable_type'),
            authorId: data_get($comment, 'author_id'),
            authorType: data_get($comment, 'author_type'),
            createdAt: self::normalizeDate(data_get($comment, 'created_at')),
            updatedAt: self::normalizeDate(data_get($comment, 'updated_at')),
        );
    }

    public function toArray(): array
    {
        return [
            'signal' => $this->signal,
            'version' => $this->version,
            'transport' => $this->transport,
            'comment' => [
                'id' => $this->commentId,
                'parent_id' => $this->parentId,
                'text' => $this->text,
                'payload' => $this->payload,
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ],
            'commentable' => [
                'id' => $this->commentableId,
                'type' => $this->commentableType,
            ],
            'author' => [
                'id' => $this->authorId,
                'type' => $this->authorType,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizePayload(mixed $payload): ?array
    {
        return is_array($payload) ? $payload : null;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toISOString();
        }

        return is_string($value) && $value !== '' ? $value : null;
    }
}
