<?php

namespace DissNik\MoonShineCommentable\Resources\Pages;

use DissNik\MoonShineCommentable\Components\CommentsBuilder;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class CommentIndexPage extends IndexPage
{
    public const LIST_COMPONENT_NAME = 'commentable-list';

    public function getListComponentName(): string
    {
        return self::LIST_COMPONENT_NAME;
    }

    public function fields(): array
    {
        return [
            ID::make(),
            Text::make('author.name'),
        ];
    }

    public function modifyListComponent(ComponentContract $component): ComponentContract
    {
        $resource = $this->getResource();

        $items = method_exists($component, 'getOriginalItems')
            ? $component->getOriginalItems()
            : [];

        /** @var CommenterContract|null $user */
        $user = MoonShineAuth::getGuard()->user() ?? auth()->user();

        $queryParams = array_filter([
            'commentable_id' => $resource->getQueryParam('commentable_id', request()->input('commentable_id')),
            'commentable_type' => $resource->getQueryParam('commentable_type', request()->input('commentable_type')),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return CommentsBuilder::make()
            ->name($this->getListComponentName())
            ->nowOn($this, $resource, $queryParams)
            ->commenter(fn (CommentContract $item): string => CommentableConfig::resolveAuthorName($item))
            ->avatar(fn (CommentContract $item): string => (string) (CommentableConfig::resolveAuthorAvatar($item) ?? ''))
            ->message('text')
            ->createdAt(fn(CommentContract $item) => $item->created_at->translatedFormat('d M H:i'))
            ->updatedAt(fn(CommentContract $item) => $item->updated_at->translatedFormat('d M H:i'))
            ->asAuthor(fn(CommentContract $item) => $item->isAuthor($user))
            ->commentId(fn (CommentContract $item) => data_get($item, 'id'))
            ->items($items)
            ->async()
            ->cast($resource->getCaster());
    }
}
