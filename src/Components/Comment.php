<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Components;

use Carbon\Carbon;
use Closure;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make(Closure|string $commenter, Closure|string $avatar, Closure|string $message, Closure|string $createdAt = null, Closure|string $updatedAt = null, Closure|bool $isAuthor = false, Closure|int|string|null $commentId = null)
 */
final class Comment extends MoonShineComponent
{
    protected string $view = 'moonshine-commentable::components.comment';

    /**
     * @param  (Closure(self):string)|string  $commenter
     * @param  (Closure(self):string)|string  $avatar
     * @param  (Closure(self):string)|string  $message
     * @param  (Closure(self):Carbon|string)|Carbon|string|null  $createdAt
     * @param  (Closure(self):Carbon|string)|Carbon|string|null  $updatedAt
     * @param  (Closure(self):bool)|bool  $isAuthor
     * @param  (Closure(self):int|string|null)|int|string|null  $commentId
     */
    public function __construct(
        protected Closure|string $commenter,
        protected Closure|string $avatar,
        protected Closure|string $message,
        protected Closure|Carbon|string|null $createdAt,
        protected Closure|Carbon|string|null $updatedAt,
        protected Closure|bool $isAuthor = false,
        protected Closure|int|string|null $commentId = null,
    )
    {
        parent::__construct();
    }

    /**
     * @param  (Closure(self):bool)|bool  $value
     *
     * @return self
     */
    public function asAuthor(Closure|bool $value = true): self
    {
        $this->isAuthor = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [
            'commenter' => value($this->commenter, $this),
            'avatar' => value($this->avatar, $this),
            'message' => value($this->message, $this),
            'createdAt' =>  value($this->createdAt, $this),
            'updatedAt' =>  value($this->updatedAt, $this),
            'isAuthor' =>  value($this->isAuthor, $this),
            'commentId' => value($this->commentId, $this),
        ];
    }
}
