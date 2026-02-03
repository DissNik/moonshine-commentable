<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Components;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(CommentContract $item)
 */
final class CommentCard extends MoonShineComponent
{
    protected string $view = 'moonshine-commentable::components.comment-card';
    protected ?bool $isAuthor = null;

    public function __construct(
        protected CommentContract $item
    )
    {
        parent::__construct();
    }

    public function asAuthor(bool $isAuthor = true): self
    {
        $this->isAuthor = $isAuthor;

        return $this;
    }

    /*
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        /** @var CommenterContract $currentUser */
        $currentUser = auth()->user();

        return [
            'item' => $this->item,
            'isAuthor' => $this->isAuthor ?? $this->item->isAuthor($currentUser),
        ];
    }
}
