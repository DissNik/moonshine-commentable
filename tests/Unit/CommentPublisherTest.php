<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Unit;

use Carbon\CarbonImmutable;
use DissNik\MoonShineCommentable\Contracts\CommentPayloadContract;
use DissNik\MoonShineCommentable\Contracts\CommentPublisherContract;
use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Support\CommentPublisher;
use DissNik\MoonShineCommentable\Tests\TestCase;

final class CommentPublisherTest extends TestCase
{
    protected bool $requiresDatabase = false;

    public function test_comment_transport_publishes_stable_created_payload(): void
    {
        config()->set('moonshine-commentable.transport.mode', 'websocket');
        config()->set('moonshine-commentable.transport.signals.comment_created', 'comments.created');
        config()->set('moonshine-commentable.transport.payload.version', 3);
        config()->set('moonshine-commentable.transport.publisher', FakeCommentPublisher::class);

        /** @var FakeCommentPublisher $publisher */
        $publisher = app(CommentPublisherContract::class);

        app(CommentPublisher::class)->publishCreated($this->makeComment());

        $this->assertCount(1, $publisher->payloads);
        $this->assertSame([
            'signal' => 'comments.created',
            'version' => 3,
            'transport' => 'websocket',
            'comment' => [
                'id' => 1,
                'parent_id' => 10,
                'text' => 'Hello',
                'payload' => ['attachments' => 1],
                'created_at' => '2026-04-05T10:00:00.000000Z',
                'updated_at' => '2026-04-05T10:05:00.000000Z',
            ],
            'commentable' => [
                'id' => 'lead-1',
                'type' => 'Modules\\LeadManagement\\Models\\Lead',
            ],
            'author' => [
                'id' => 'user-1',
                'type' => 'App\\Models\\User',
            ],
        ], $publisher->payloads[0]->toArray());
    }

    private function makeComment(): Comment
    {
        $comment = (new Comment)->setDateFormat('Y-m-d H:i:s');
        $comment->id = 1;
        $comment->parent_id = 10;
        $comment->text = 'Hello';
        $comment->payload = ['attachments' => 1];
        $comment->commentable_id = 'lead-1';
        $comment->commentable_type = 'Modules\\LeadManagement\\Models\\Lead';
        $comment->author_id = 'user-1';
        $comment->author_type = 'App\\Models\\User';
        $comment->created_at = CarbonImmutable::parse('2026-04-05 10:00:00 UTC');
        $comment->updated_at = CarbonImmutable::parse('2026-04-05 10:05:00 UTC');

        return $comment;
    }
}

final class FakeCommentPublisher implements CommentPublisherContract
{
    /**
     * @var list<CommentPayloadContract>
     */
    public array $payloads = [];

    public function publish(CommentPayloadContract $payload): void
    {
        $this->payloads[] = $payload;
    }
}
