<?php
namespace DissNik\MoonShineCommentable\Resources;

use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Crud\JsonResponse;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\SkipMenu;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\Enums\ToastType;

#[SkipMenu]
class CommentResource extends ModelResource
{
    protected string $model = Comment::class;

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
        $commentableId = request()->input('commentable_id');
        $commentableType = request()->input('commentable_type');

        if ($commentableId !== null && $commentableId !== '') {
            $builder->where('commentable_id', $commentableId);
        }

        if ($commentableType !== null && $commentableType !== '') {
            $builder->where('commentable_type', $commentableType);
        }

        return parent::modifyQueryBuilder($builder);
    }

    public function modifySaveResponse(JsonResponse $response): JsonResponse
    {
        return $response
            ->toast(__('moonshine-commentable::ui.message_added'), ToastType::SUCCESS);
    }
}
