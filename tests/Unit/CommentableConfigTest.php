<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Unit;

use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Resources\CommentResource;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use DissNik\MoonShineCommentable\Tests\TestCase;

final class CommentableConfigTest extends TestCase
{
    public function test_new_nested_config_keys_take_priority(): void
    {
        config()->set('moonshine-commentable.transport.mode', 'polling');
        config()->set('moonshine-commentable.models.comment', 'App\\CustomComment');
        config()->set('moonshine-commentable.moonshine.resource', 'App\\MoonShine\\Resources\\CustomCommentResource');
        config()->set('moonshine-commentable.moonshine.events.comment_added', 'comments:created');
        config()->set('moonshine-commentable.transport.polling.interval', 15);

        $this->assertSame('App\\CustomComment', CommentableConfig::commentModel());
        $this->assertSame('App\\MoonShine\\Resources\\CustomCommentResource', CommentableConfig::moonShineResource());
        $this->assertSame('comments:created', CommentableConfig::commentAddedEvent());
        $this->assertSame(15, CommentableConfig::pollingInterval());
    }

    public function test_default_values_remain_stable_when_nested_keys_are_absent(): void
    {
        config()->set('moonshine-commentable.transport.polling.interval', null);

        $this->assertSame(Comment::class, CommentableConfig::commentModel());
        $this->assertSame('600px', CommentableConfig::commentsHeight());
        $this->assertSame(200, CommentableConfig::scrollThreshold());
        $this->assertNull(CommentableConfig::pollingInterval());
        $this->assertSame(CommentResource::class, CommentableConfig::moonShineResource());
    }

    public function test_transport_mode_is_configurable(): void
    {
        config()->set('moonshine-commentable.transport.mode', 'websocket');

        $this->assertSame('websocket', CommentableConfig::transportMode());
    }
}
