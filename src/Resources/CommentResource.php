<?php
namespace DissNik\MoonShineCommentable\Resources;

use DissNik\MoonShineCommentable\Contracts\CommentContract;
use DissNik\MoonShineCommentable\Contracts\CommentableContract;
use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use Illuminate\Validation\ValidationException;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\Enums\ToastType;

#[SkipMenu]
class CommentResource extends ModelResource
{
    protected string $model = Comment::class;

    protected bool $withPolicy = true;

    protected string $sortColumn = 'created_at';


    protected string $queryParamPrefix = 'comment_';

    protected bool $usePagination = false;

    protected SortDirection $sortDirection = SortDirection::ASC;

    protected array $with = ['author'];

    public function getModel(): Model
    {
        $class = CommentableConfig::commentModel();

        return new $class();
    }

    protected function pages(): array
    {
        return [
            CommentableConfig::moonShineIndexPage(),
            CommentableConfig::moonShineFormPage(),
        ];
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $commentableId = $this->contextParam('commentable_id');
        $commentableType = $this->contextParam('commentable_type');

        if ($commentableId === null || $commentableId === '' || $commentableType === null || $commentableType === '') {
            return parent::modifyQueryBuilder($builder->whereRaw('1 = 0'));
        }

        $commentable = CommentableConfig::resolveCommentable($commentableId, (string) $commentableType);
        $actor = $this->currentCommenter();

        if (
            ! $commentable instanceof CommentableContract
            || ! CommentableConfig::authorizeCommentable($commentable, 'view', $actor)
        ) {
            return parent::modifyQueryBuilder($builder->whereRaw('1 = 0'));
        }

        $builder
            ->where('commentable_id', $commentable->getKey())
            ->where('commentable_type', $commentable->getMorphClass());

        return parent::modifyQueryBuilder($builder);
    }

    public function modifySaveResponse(JsonResponse $response): JsonResponse
    {
        return $response
            ->toast(__('moonshine-commentable::ui.message_added'), ToastType::SUCCESS);
    }

    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        /** @var Comment $comment */
        $comment = $item->getOriginal();
        $commentable = $this->resolveAuthorizedCommentableFromRequest('create');
        $author = $this->currentCommenter();

        if (! $author instanceof CommenterContract) {
            throw new AuthorizationException('Unauthenticated comment author');
        }

        $parent = $this->resolveParentComment($this->requestParam('parent_id'), $commentable);

        if (
            $parent instanceof CommentContract
            && ! CommentableConfig::authorizeCommentable($commentable, 'reply', $author, $parent)
        ) {
            throw new AuthorizationException('Cannot reply to this comment');
        }

        request()->merge([
            'commentable_id' => $commentable->getKey(),
            'commentable_type' => $commentable->getMorphClass(),
            'author_id' => $author->getKey(),
            'author_type' => $author->getMorphClass(),
            'parent_id' => $parent?->getKey(),
        ]);

        $comment->commentable_id = $commentable->getKey();
        $comment->commentable_type = $commentable->getMorphClass();
        $comment->author_id = $author->getKey();
        $comment->author_type = $author->getMorphClass();
        $comment->parent_id = $parent?->getKey();

        return $item;
    }

    protected function beforeUpdating(DataWrapperContract $item): DataWrapperContract
    {
        /** @var CommentContract $comment */
        $comment = $item->getOriginal();
        $actor = $this->currentCommenter();

        if (
            ! $actor instanceof CommenterContract
            || ! CommentableConfig::authorizeCommentable($comment->commentable, 'update', $actor, $comment)
        ) {
            throw new AuthorizationException('Cannot update this comment');
        }

        $this->resolveParentComment($comment->parent_id, $comment->commentable);

        request()->merge([
            'commentable_id' => data_get($comment, 'commentable_id'),
            'commentable_type' => data_get($comment, 'commentable_type'),
            'author_id' => data_get($comment, 'author_id'),
            'author_type' => data_get($comment, 'author_type'),
            'parent_id' => data_get($comment, 'parent_id'),
        ]);

        return $item;
    }

    protected function beforeDeleting(DataWrapperContract $item): DataWrapperContract
    {
        /** @var CommentContract $comment */
        $comment = $item->getOriginal();
        $actor = $this->currentCommenter();

        if (
            ! $actor instanceof CommenterContract
            || ! CommentableConfig::authorizeCommentable($comment->commentable, 'delete', $actor, $comment)
        ) {
            throw new AuthorizationException('Cannot delete this comment');
        }

        return $item;
    }

    protected function currentCommenter(): ?CommenterContract
    {
        try {
            $user = MoonShineAuth::getGuard()->user() ?? auth()->user();
        } catch (Throwable) {
            $user = auth()->user();
        }

        return $user instanceof CommenterContract ? $user : null;
    }

    protected function resolveAuthorizedCommentableFromRequest(string $ability): CommentableContract
    {
        $commentable = CommentableConfig::resolveCommentable(
            $this->requestParam('commentable_id'),
            $this->requestParam('commentable_type'),
        );

        $actor = $this->currentCommenter();

        if (
            ! $commentable instanceof CommentableContract
            || ! CommentableConfig::authorizeCommentable($commentable, $ability, $actor)
        ) {
            throw new AuthorizationException('Cannot access this commentable resource');
        }

        return $commentable;
    }

    protected function resolveParentComment(mixed $parentId, CommentableContract $commentable): ?CommentContract
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        $commentModel = CommentableConfig::commentModel();
        $parent = $commentModel::query()->find($parentId);

        if (! $parent instanceof CommentContract) {
            throw ValidationException::withMessages([
                'parent_id' => __('validation.exists', ['attribute' => 'parent_id']),
            ]);
        }

        if (
            data_get($parent, 'commentable_id') !== $commentable->getKey()
            || data_get($parent, 'commentable_type') !== $commentable->getMorphClass()
        ) {
            throw ValidationException::withMessages([
                'parent_id' => __('validation.exists', ['attribute' => 'parent_id']),
            ]);
        }

        return $parent;
    }

    protected function requestParam(string $key): mixed
    {
        $value = request()->input($key);

        if ($value !== null && $value !== '') {
            return $value;
        }

        return request()->input($this->queryParamPrefix . $key);
    }

    protected function contextParam(string $key): mixed
    {
        $value = $this->getQueryParam($key);

        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->requestParam($key);
    }
}
