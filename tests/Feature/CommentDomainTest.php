<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Feature;

use DissNik\MoonShineCommentable\Contracts\CommentPayloadContract;
use DissNik\MoonShineCommentable\Contracts\CommentPublisherContract;
use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Tests\TestCase;
use DissNik\MoonShineCommentable\Traits\HasComments;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class CommentDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moonshine-commentable.models.comment', Comment::class);
        config()->set('moonshine-commentable.transport.publisher', DomainFakePublisher::class);

        Schema::dropIfExists('comments');
        Schema::dropIfExists('comment_reads');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('users');

        Schema::create('posts', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->string('commentable_id');
            $table->string('commentable_type');
            $table->string('author_id')->nullable();
            $table->string('author_type')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('text');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('comment_reads', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('commentable_id');
            $table->string('commentable_type');
            $table->string('reader_id');
            $table->string('reader_type');
            $table->timestamp('last_read_at');
            $table->timestamps();
        });
    }

    public function test_comment_creation_uses_new_contract_without_self_passed_commentable_argument(): void
    {
        $post = PackageTestPost::query()->create(['id' => 'post-1', 'title' => 'Post']);
        $author = PackageTestUser::query()->create(['id' => 'user-1', 'name' => 'Author']);

        $comment = $post->comment('Hello world', $author, payload: ['attachments' => 1]);

        $this->assertSame('Hello world', $comment->text);
        $this->assertSame(['attachments' => 1], $comment->payload);
        $this->assertSame('post-1', $comment->commentable_id);
        $this->assertSame(PackageTestPost::class, $comment->commentable_type);
        $this->assertSame('user-1', $comment->author_id);

        /** @var DomainFakePublisher $publisher */
        $publisher = app(CommentPublisherContract::class);

        $this->assertCount(1, $publisher->payloads);
        $this->assertSame('Hello world', $publisher->payloads[0]->toArray()['comment']['text']);
    }

    public function test_reply_parent_must_belong_to_the_same_commentable(): void
    {
        $firstPost = PackageTestPost::query()->create(['id' => 'post-1', 'title' => 'First']);
        $secondPost = PackageTestPost::query()->create(['id' => 'post-2', 'title' => 'Second']);
        $author = PackageTestUser::query()->create(['id' => 'user-1', 'name' => 'Author']);

        $foreignComment = $firstPost->comment('Foreign', $author);

        $this->expectException(ValidationException::class);

        $secondPost->comment('Reply', $author, $foreignComment);
    }

    public function test_commentable_authorization_callback_can_block_comment_creation(): void
    {
        config()->set('moonshine-commentable.commentables.authorize', static function (
            CommentableContract $commentable,
            string $ability,
        ): bool {
            return $ability !== 'create';
        });

        $post = PackageTestPost::query()->create(['id' => 'post-1', 'title' => 'Post']);
        $author = PackageTestUser::query()->create(['id' => 'user-1', 'name' => 'Author']);

        $this->expectException(AuthorizationException::class);

        $post->comment('Blocked', $author);
    }
}

final class PackageTestPost extends Model implements CommentableContract
{
    use HasComments;

    protected $table = 'posts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}

final class PackageTestUser extends Model implements CommenterContract
{
    use Authorizable;

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function getCommenterName(): string
    {
        return (string) $this->name;
    }

    public function getCommenterAvatar(): ?string
    {
        return null;
    }
}

final class DomainFakePublisher implements CommentPublisherContract
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
