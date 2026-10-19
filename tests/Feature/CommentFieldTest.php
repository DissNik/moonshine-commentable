<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests\Feature;

use DissNik\MoonShineCommentable\Components\CommentsBuilder;
use DissNik\MoonShineCommentable\Fields\Comment as CommentField;
use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Resources\CommentResource;
use DissNik\MoonShineCommentable\Resources\Pages\CommentFormPage;
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
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Providers\MoonShineServiceProvider;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Components\Components;

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

    public function test_field_form_refreshes_its_own_list_resets_input_and_dispatches_the_configured_event(): void
    {
        (new MoonShineServiceProvider($this->app))->register();
        config()->set('moonshine-commentable.moonshine.events.comment_added', 'package:comment-created');
        $form = $this->field(PackageFieldProbe::class)->formData()['form'];

        self::assertSame(AlpineJs::prepareEvents([
            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'commentable-list', [
                'commentable_id' => 'post-1', 'commentable_type' => PackageFieldPost::class,
            ]),
            AlpineJs::event(JsEvent::FORM_RESET, 'comment_form'),
            AlpineJs::event('package:comment-created'),
        ]), $form->getAsyncEvents());
        self::assertSame('comment_form', $form->getName());
    }

    public function test_field_view_omits_teleport_without_a_form_and_keeps_writable_forms(): void
    {
        $provider = new MoonShineServiceProvider($this->app);
        $provider->register();
        $provider->boot();
        $data = [
            'isPersisted' => true,
            'comments' => '<div>История сообщений</div>',
            'formComponents' => Components::make([]),
            'fillAvailableSpace' => true,
        ];
        $readonly = view('moonshine-commentable::fields.comment', $data + ['form' => ''])->render();
        self::assertStringContainsString('История сообщений', $readonly);
        self::assertStringNotContainsString('x-teleport', $readonly);

        $writable = view('moonshine-commentable::fields.comment', $data + ['form' => '<form id="generic-comment-form"></form>'])->render();
        self::assertStringContainsString('<template x-teleport="body">', $writable);
        self::assertStringContainsString('<form id="generic-comment-form">', $writable);
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

    public function formData(): array
    {
        return $this->viewData();
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
    public function getFormPage(): ?PageContract
    {
        return app(CommentFormPage::class);
    }

    public function getRoute(?string $name = null, DataWrapperContract|int|string|null $key = null, array $query = []): string
    {
        return '/comments';
    }

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
