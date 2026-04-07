<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Feature;

use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Resources\CommentResource;
use DissNik\MoonShineCommentable\Tests\TestCase;
use DissNik\MoonShineCommentable\Traits\HasComments;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\TypeCasts\ModelDataWrapper;

final class CommentResourceSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moonshine-commentable.models.comment', Comment::class);

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

    public function test_resource_returns_no_comments_without_an_explicit_commentable_scope(): void
    {
        PackageSecurityPost::query()->create(['id' => 'post-1', 'title' => 'Post']);
        Comment::query()->create([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
            'text' => 'Comment',
        ]);

        request()->query->replace([]);

        $count = $this->makeResource()->applyModify(Comment::query())->count();

        $this->assertSame(0, $count);
    }

    public function test_resource_hides_comments_when_host_authorization_rejects_view_access(): void
    {
        config()->set('moonshine-commentable.commentables.authorize', static function (
            CommentableContract $commentable,
            string $ability,
        ): bool {
            return $ability !== 'view';
        });

        PackageSecurityPost::query()->create(['id' => 'post-1', 'title' => 'Post']);
        Comment::query()->create([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
            'text' => 'Comment',
        ]);

        request()->query->replace([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
        ]);

        $count = $this->makeResource()->applyModify(Comment::query())->count();

        $this->assertSame(0, $count);
    }

    public function test_resource_filters_comments_to_the_resolved_commentable_pair(): void
    {
        PackageSecurityPost::query()->create(['id' => 'post-1', 'title' => 'Post']);
        PackageSecurityPost::query()->create(['id' => 'post-2', 'title' => 'Other']);

        Comment::query()->create([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
            'text' => 'Visible',
        ]);

        Comment::query()->create([
            'commentable_id' => 'post-2',
            'commentable_type' => PackageSecurityPost::class,
            'text' => 'Hidden',
        ]);

        request()->query->replace([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
        ]);

        $comments = $this->makeResource()->applyModify(Comment::query())->pluck('id')->all();

        $this->assertSame([1], $comments);
    }

    public function test_resource_keeps_comments_visible_with_explicit_resource_query_scope(): void
    {
        PackageSecurityPost::query()->create(['id' => 'post-1', 'title' => 'Post']);

        Comment::query()->create([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
            'text' => 'Visible',
        ]);

        request()->query->replace([]);

        $resource = $this->makeResource()->setQueryParams([
            'comment_commentable_id' => 'post-1',
            'comment_commentable_type' => PackageSecurityPost::class,
        ]);

        $comments = $resource->applyModify(Comment::query())->pluck('text')->all();

        $this->assertSame(['Visible'], $comments);
    }

    public function test_before_creating_merges_canonical_request_values_for_protected_fields(): void
    {
        PackageSecurityPost::query()->create(['id' => 'post-1', 'title' => 'Post']);

        $author = PackageSecurityUser::query()->create([
            'id' => 'user-1',
            'name' => 'Author',
        ]);

        auth()->setUser($author);

        request()->replace([
            'commentable_id' => 'forged-post',
            'commentable_type' => 'Forged\\Model',
            'author_id' => 'forged-user',
            'author_type' => 'Forged\\User',
            'parent_id' => 'forged-parent',
            'text' => 'Visible',
        ]);

        request()->merge([
            'commentable_id' => 'post-1',
            'commentable_type' => PackageSecurityPost::class,
            'parent_id' => null,
        ]);

        $comment = new Comment;

        $this->makeResource()->runBeforeCreating(new ModelDataWrapper($comment));

        $this->assertSame('post-1', request()->input('commentable_id'));
        $this->assertSame(PackageSecurityPost::class, request()->input('commentable_type'));
        $this->assertSame('user-1', request()->input('author_id'));
        $this->assertSame(PackageSecurityUser::class, request()->input('author_type'));
        $this->assertNull(request()->input('parent_id'));
    }

    private function makeResource(): CommentResourceSecurityProxy
    {
        return new CommentResourceSecurityProxy(app(CoreContract::class));
    }
}

final class PackageSecurityPost extends Model implements CommentableContract
{
    use HasComments;

    protected $table = 'posts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}

final class PackageSecurityUser extends Authenticatable implements CommenterContract
{
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

final class CommentResourceSecurityProxy extends CommentResource
{
    public function applyModify(Builder $builder): Builder
    {
        return $this->modifyQueryBuilder($builder);
    }

    public function runBeforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        return $this->beforeCreating($item);
    }
}
