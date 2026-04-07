<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Components;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use MoonShine\Contracts\Core\TypeCasts\DataCasterContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\HasAsyncContract;
use MoonShine\Core\Traits\NowOn;
use MoonShine\Crud\Components\Fragment;
use MoonShine\UI\Components\Components;
use MoonShine\UI\Components\IterableComponent;
use MoonShine\UI\Traits\HasAsync;
use Throwable;

use function call_user_func;
use function is_null;
use function is_string;

/**
 * @template TData of mixed = mixed
 * @template TCaster of DataCasterContract<TData> = DataCasterContract
 * @template TWrapper of DataWrapperContract<TData> = DataWrapperContract
 *
 * @method static static make(iterable $items = [])
 *
 * @extends IterableComponent<TData,TCaster,TWrapper>
 */
final class CommentsBuilder extends IterableComponent implements HasAsyncContract
{
    use HasAsync;
    use NowOn;

    protected string $view = 'moonshine-commentable::components.comments';

    protected array $translates = [
        'search' => 'moonshine::ui.search',
        'notfound' => 'moonshine::ui.notfound',
    ];

    /**
     * @var list<ComponentContract>
     */
    protected array $components = [];

    /**
     * @var (Closure(mixed, int, self): string)|string
     */
    protected Closure|string $commenter = '';

    /**
     * @var (Closure(mixed, int, self): string)|string
     */
    protected Closure|string $avatar = '';

    /**
     * @var (Closure(mixed, int, self): string)|string
     */
    protected Closure|string $message = '';

    /**
     * @var (Closure(mixed, int, self): Carbon|string)|Carbon|string|null
     */
    protected Closure|Carbon|string|null $createdAt = null;

    /**
     * @var (Closure(mixed, int, self): Carbon|string)|Carbon|string|null
     */
    protected Closure|Carbon|string|null $updatedAt = null;

    /**
     * @var (Closure(mixed, int, self): bool)|bool
     */
    protected Closure|bool $isAuthor = false;

    /**
     * @var (Closure(mixed, int, self): int|string|null)|int|string|null
     */
    protected Closure|int|string|null $commentId = null;

    /**
     * @var null|Closure(mixed, int, self): ComponentContract
     */
    protected ?Closure $customComponent = null;

    /**
     * @var (Closure(mixed, int, self): array<string, mixed>)|array<string, mixed>
     */
    protected array|Closure $componentAttributes = [];

    protected ?Closure $topLeft = null;

    protected ?Closure $topRight = null;

    protected bool $searchable = false;

    protected null|int|string $commentableId = null;

    protected ?string $commentableType = null;

    /**
     * @param  iterable<array-key, TData>  $items
     */
    public function __construct(
        iterable $items = [],
    ) {
        parent::__construct();

        $this->items($items);

        $this->withAttributes([]);
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): string)|string  $value
     */
    public function commenter(Closure|string $value): self
    {
        $this->commenter = $value;

        return $this;
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): string)|string  $value
     */
    public function avatar(Closure|string $value): self
    {
        $this->avatar = $value;

        return $this;
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): string)|string  $value
     */
    public function message(Closure|string $value): self
    {
        $this->message = $value;

        return $this;
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): Carbon|string)|Carbon|string|null  $value
     */
    public function createdAt(Closure|Carbon|string|null $value): self
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): Carbon|string)|Carbon|string|null  $value
     */
    public function updatedAt(Closure|Carbon|string|null $value): self
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): bool)|bool  $value
     */
    public function asAuthor(Closure|bool $value = true): self
    {
        $this->isAuthor = $value;

        return $this;
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): int|string|null)|int|string|null  $value
     */
    public function commentId(Closure|int|string|null $value): self
    {
        $this->commentId = $value;

        return $this;
    }

    protected function prepareAsyncUrl(Closure|string|null $url = null): Closure|string
    {
        return $url ?? fn (): string => $this->getCore()->getRouter()->getEndpoints()->component(name: $this->getName());
    }

    /**
     * @param  (Closure(mixed $data, int $index, self $ctx): array<string, mixed>)|array<string, mixed>  $attributes
     */
    public function componentAttributes(array|Closure $attributes): self
    {
        $this->componentAttributes = $attributes;

        return $this;
    }

    /**
     * @param  Closure(mixed $data, int $index, self $ctx): ComponentContract  $component
     */
    public function customComponent(Closure $component): self
    {
        $this->customComponent = $component;

        return $this;
    }

    public function commentable(null|int|string $id, ?string $type = null): self
    {
        $this->commentableId = $id;
        $this->commentableType = $type;

        return $this;
    }

    public function getComments(): Components
    {
        /** @var Collection<array-key, Comment> $items */
        $items = $this->getItems()->map(function (mixed $data, int $index) {
            if (! is_null($this->customComponent)) {
                return call_user_func($this->customComponent, $data, $index, $this);
            }

            return Comment::make(...$this->getMapper($data, $index))
                ->customAttributes(value($this->componentAttributes, $data, $index, $this));
        });

        $fragmentParams = array_filter([
            ...$this->getNowOnQueryParams(),
            'commentable_id' => $this->commentableId,
            'commentable_type' => $this->commentableType,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return Components::make([
            Fragment::make($items)
                ->name($this->getName())
                ->updateWith(
                    $fragmentParams,
                    $this->getNowOnResource(),
                    $this->getNowOnPage(),
                )
                ->withSelectorsParams([
                    'commentable_id' => '#comment_form [name="commentable_id"]',
                    'commentable_type' => '#comment_form [name="commentable_type"]',
                ])
                ->customAttributes([
                    'data-comments-items' => $this->getName(),
                ])
                ->class('space-y-2')
                ->when(
                    $this->commentableId === null
                    && $this->commentableType === null
                    && CommentableConfig::transportMode() === 'polling'
                    && ($interval = CommentableConfig::pollingInterval()),
                    fn (Fragment $fragment) => $fragment->autoUpdate($interval)
                ),
        ]);
    }

    /**
     * @return Closure|Carbon|string|array|bool<string, string>
     */
    protected function getMapperValue(string $column, mixed $data, int $index): Closure|Carbon|string|array|bool
    {
        return is_string($this->{$column})
            ? data_get($data, $this->{$column}, '')
            : value($this->{$column}, $data, $index, $this);
    }

    /**
     * @param  TData  $data
     * @return array<string, mixed>
     */
    protected function getMapper(mixed $data, int $index): array
    {
        return [
            'commenter' => $this->getMapperValue('commenter', $data, $index),
            'avatar' => $this->getMapperValue('avatar', $data, $index),
            'message' => $this->getMapperValue('message', $data, $index),
            'createdAt' => $this->getMapperValue('createdAt', $data, $index),
            'updatedAt' => $this->getMapperValue('updatedAt', $data, $index),
            'isAuthor' => $this->getMapperValue('isAuthor', $data, $index),
            'commentId' => $this->getMapperValue('commentId', $data, $index),
        ];
    }

    /**
     * @param  Closure(self): list<ComponentContract>  $callback
     */
    public function topLeft(Closure $callback): self
    {
        $this->topLeft = $callback;

        return $this;
    }

    /**
     * @param  Closure(self): list<ComponentContract>  $callback
     */
    public function topRight(Closure $callback): self
    {
        $this->topRight = $callback;

        return $this;
    }

    public function searchable(): self
    {
        $this->searchable = true;

        return $this;
    }

    private function getTopLeft(): Components
    {
        $components = is_null($this->topLeft) ? [] : call_user_func($this->topLeft);

        return Components::make($components);
    }

    private function getTopRight(): Components
    {
        $components = is_null($this->topRight) ? [] : call_user_func($this->topRight);

        return Components::make($components);
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    protected function prepareBeforeRender(): void
    {
        parent::prepareBeforeRender();

        $this->performBeforeRender();
    }

    protected function performBeforeRender(): self
    {
        $this->resolvePaginator();

        if ($this->isAsync() && $this->hasPaginator()) {
            $this->paginator(
                $this->getPaginator()
                    ?->setPath($this->prepareAsyncUrlFromPaginator())
            );
        }

        if ($this->isAsync()) {
            $this->customAttributes([
                'data-events' => $this->getAsyncEvents(),
            ]);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    protected function viewData(): array
    {
        return [
            'comments' => $this->getComments(),
            'name' => $this->getName(),
            'hasPaginator' => $this->hasPaginator(),
            'paginator' => $this->getPaginator(
                $this->isAsync()
            ),
            'async' => $this->isAsync(),
            'asyncUrl' => $this->getAsyncUrl(),
            'topLeft' => $this->getTopLeft(),
            'topRight' => $this->getTopRight(),
            'searchable' => $this->isSearchable(),
            'searchValue' => $this->getCore()->getRequest()->getScalar('search', ''),
            'commentsHeight' => CommentableConfig::commentsHeight(),
            'commentsListConfig' => [
                'listName' => $this->getName(),
                'threshold' => CommentableConfig::scrollThreshold(),
                'commentAddedEvent' => CommentableConfig::commentAddedEvent(),
            ],
        ];
    }
}
