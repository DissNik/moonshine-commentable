<?php

namespace DissNik\MoonShineCommentable\Resources\Comment\Pages;

use DissNik\MoonShineCommentable\Components\CommentCard;
use DissNik\MoonShineCommentable\Contracts\CommentContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Components\CardsBuilder;
use MoonShine\UI\Fields\ID;

class CommentIndexPage extends IndexPage
{
    public function fields(): array
    {
        return [
            ID::make(),
        ];
    }

    public function modifyListComponent(ComponentContract $component): ComponentContract
    {
        $resource = $this->getResource();

        $items = method_exists($component, 'getOriginalItems')
            ? $component->getOriginalItems()
            : [];

        return CardsBuilder::make()
            ->name($this->getListComponentName())
            ->columnSpan(12)
            ->items($items)
            ->async()
            ->cast($resource->getCaster())
            ->customComponent(function (CommentContract $item) {
                $isAuthor = false;

                if (auth()->check()) {
                    $isAuthor = auth()->id() === $item->author?->getKey();
                }

                return CommentCard::make($item)
                    ->asAuthor($isAuthor);
            });
    }
}
