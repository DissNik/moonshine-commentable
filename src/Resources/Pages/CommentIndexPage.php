<?php

namespace DissNik\MoonShineCommentable\Resources\Pages;

use DissNik\MoonShineCommentable\Components\CommentsBuilder;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class CommentIndexPage extends IndexPage
{
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

        /** @var CommenterContract $user */
        $user = auth()->user();

        return CommentsBuilder::make()
            ->name($this->getListComponentName())
            ->commenter('author.name')
            ->avatar('author.avatar_url')
            ->message('text')
            ->createdAt(fn(CommentContract $item) => $item->created_at->translatedFormat('d M H:i'))
            ->updatedAt(fn(CommentContract $item) => $item->updated_at->translatedFormat('d M H:i'))
            ->asAuthor(fn(CommentContract $item) => $item->isAuthor($user))
            ->items($items)
            ->async()
            ->cast($resource->getCaster());
    }
}
