<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Unit;

use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Resources\Pages\CommentFormPage;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use DissNik\MoonShineCommentable\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;

final class CommentableConfigTest extends TestCase
{
    protected bool $requiresDatabase = false;

    public function test_default_presenters_keep_author_details_and_handle_a_missing_author(): void
    {
        $comment = (new Comment)->setRelation('author', new Fluent([
            'name' => 'Автор сообщения',
            'avatar_url' => '/avatars/comment-author.png',
        ]));

        self::assertSame('Автор сообщения', CommentableConfig::resolveAuthorName($comment));
        self::assertSame('/avatars/comment-author.png', CommentableConfig::resolveAuthorAvatar($comment));

        $comment->setRelation('author', null);
        self::assertSame('', CommentableConfig::resolveAuthorName($comment));
        self::assertNull(CommentableConfig::resolveAuthorAvatar($comment));
    }

    public function test_comment_form_accepts_only_text_from_three_to_255_unicode_characters(): void
    {
        $rules = app(CommentFormPage::class)->rules(new ModelDataWrapper(new Comment));

        foreach (['', 'Яя', str_repeat('Я', 256), 123] as $text) {
            $this->assertTrue(Validator::make(['text' => $text], $rules)->fails(), 'Invalid message was accepted.');
        }
        foreach (['Яяя', str_repeat('Я', 255)] as $text) {
            $this->assertTrue(Validator::make(['text' => $text], $rules)->passes(), 'Valid Unicode message was rejected.');
        }
    }
}
