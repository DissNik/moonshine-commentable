<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Feature;

use DissNik\MoonShineCommentable\Components\CommentsBuilder;
use DissNik\MoonShineCommentable\Fields\Comment as CommentField;
use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Resources\CommentResource;
use DissNik\MoonShineCommentable\Resources\Pages\CommentIndexPage;
use DissNik\MoonShineCommentable\Tests\TestCase;
use DissNik\MoonShineCommentable\Traits\HasComments;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use MoonShine\Contracts\AssetManager\AssetManagerContract;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\ComponentContract;

final class CommentFieldTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $core = app(CoreContract::class);
        $assets = Mockery::mock(AssetManagerContract::class);
        $assets->shouldReceive('add')->andReturnSelf();
        $core->shouldReceive('getContainer')->with(AssetManagerContract::class)->andReturn($assets);
        config()->set('moonshine-commentable.models.comment', Comment::class);
        Schema::create('field_posts', function (Blueprint $table): void {
            $table->string('id')->primary();
        });
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->string('commentable_id');
            $table->string('commentable_type');
            $table->text('text');
            $table->timestamps();
        });
        foreach (['post-1' => 'First discussion', 'post-2' => 'Second discussion'] as $id => $text) {
            PackageFieldPost::query()->create(['id' => $id]);
            Comment::query()->create(['commentable_id' => $id, 'commentable_type' => PackageFieldPost::class, 'text' => $text]);
        }
    }

    public function test_default_field_keeps_comments_scoped_to_its_model_relation(): void
    {
        $component = $this->field(PackageFieldProbe::class)->listComponent();

        self::assertSame(['First discussion'], collect($component->getOriginalItems())->pluck('text')->all());
    }

    public function test_field_uses_the_overridden_query_without_replacing_the_package_list_builder(): void
    {
        $field = $this->field(PackageOverriddenFieldProbe::class);
        $component = $field->listComponent();

        self::assertSame(['Second discussion'], collect($component->getOriginalItems())->pluck('text')->all());
        self::assertSame('post-1', $field->getResource()->getQueryParam('commentable_id'));
        self::assertSame(PackageFieldPost::class, $field->getResource()->getQueryParam('commentable_type'));
    }

    public function test_field_can_fill_available_space_without_changing_the_default_list_layout(): void
    {
        $field = $this->field(PackageFieldProbe::class);
        self::assertFalse($field->listComponent()->isFillAvailableSpace());
        self::assertTrue($field->fillAvailableSpace()->listComponent()->isFillAvailableSpace());
        self::assertFalse($field->fillAvailableSpace(false)->listComponent()->isFillAvailableSpace());
    }

    /** @param class-string<PackageFieldProbe> $class */
    private function field(string $class): PackageFieldProbe
    {
        return new $class(PackageFieldPost::query()->findOrFail('post-1'), new PackageFieldResource(app(CoreContract::class)));
    }
}

final class PackageFieldPost extends Model
{
    use HasComments;

    protected $table = 'field_posts';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}

class PackageFieldProbe extends CommentField
{
    public function __construct(Model $subject, CommentResource $resource)
    {
        $this->setRelatedModel($subject);
        $this->setRelationName('comments');
        $this->setResource($resource);
    }

    public function listComponent(): CommentsBuilder
    {
        return $this->getCardBuilder();
    }
}

final class PackageOverriddenFieldProbe extends PackageFieldProbe
{
    protected function getCommentsQuery(?Model $model): ?Builder
    {
        return Comment::query()->where('commentable_id', 'post-2');
    }
}

final class PackageFieldResource extends CommentResource
{
    public function getIndexPage(): ?PageContract
    {
        return new PackageFieldIndexPage($this->customQueryBuilder);
    }
}

final class PackageFieldIndexPage extends CommentIndexPage
{
    public function __construct(private Builder $commentsQuery) {}

    public function getListComponent(bool $withoutFragment = false): ComponentContract
    {
        return CommentsBuilder::make($this->commentsQuery->get());
    }
}
