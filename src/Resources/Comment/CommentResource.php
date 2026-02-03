<?php
namespace DissNik\MoonShineCommentable\Resources\Comment;

use DissNik\MoonShineCommentable\Models\Comment;
use DissNik\MoonShineCommentable\Resources\Comment\Pages\CommentFormPage;
use DissNik\MoonShineCommentable\Resources\Comment\Pages\CommentIndexPage;
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
        $class = config('moonshine-commentable.comment.model');

        return new $class();
    }

    protected function pages(): array
    {
        return [
            CommentIndexPage::class,
            CommentFormPage::class,
        ];
    }

    public function modifySaveResponse(JsonResponse $response): JsonResponse
    {
        return $response
            ->toast(__('moonshine-commentable::ui.message_added'), ToastType::SUCCESS);
    }
}
