<?php

namespace DissNik\MoonShineCommentable\Fields;

use Closure;
use DissNik\MoonShineCommentable\Resources\Comment\CommentResource;
use Illuminate\Support\Collection;
use MoonShine\AssetManager\Css;
use MoonShine\Contracts\Core\HasComponentsContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\HasFieldsContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Components\Components;
use MoonShine\UI\Components\FormBuilder;
use function is_null;

class Comment extends HasMany
{
    protected string $view = 'moonshine-commentable::fields.comment';
    protected bool $outsideComponent = false;

    public function __construct(
        Closure|string $label,
        ?string $relationName = null,
        Closure|string|null $formatted = null,
        ?ModelResource $resource = null
    ) {
        if (is_null($resource)) {
            $resource = CommentResource::class;
        }

        parent::__construct($label, $relationName, $formatted, $resource);
    }

    protected function assets(): array
    {
        return [
            Css::make('vendor/moonshine-commentable/css/stylesheet.css'),
        ];
    }

    protected function getCardBuilder()
    {
        $casted = $this->getRelatedModel();

        $relation = $casted?->{$this->getRelationName()}();

        /** @var ModelResource $resource */
        $resource = $this->getResource();

        return $resource
            ->customQueryBuilder($relation)
            ->getIndexPage()
            ->getListComponent();
    }

    protected function prepareComponents(iterable $components, string $formId, $data): Collection
    {
        return collect($components)
            ->map(function ($component) use ($formId, $data) {
                if ($component instanceof FieldContract) {
                    $component->customAttributes(['form' => $formId]);
                    $column = $component->getColumn();

                    if (isset($data[$column])) {
                        $component->setValue($data[$column]);
                    }
                }

                if ($component instanceof HasFieldsContract || $component instanceof HasComponentsContract) {
                    $children = $component instanceof HasFieldsContract
                        ? $component->getFields()
                        : $component->getComponents();

                    $this->prepareComponents($children, $formId, $data);
                }

                return $component;
            });
    }

    protected function viewData(): array
    {
        $item = $this->getRelatedModel();

        if (is_null($item) || !$item->exists) {
            return [
                'isPersisted' => false
            ];
        }

        $formId = 'comment_form';
        $resource = $this->getResource();

        $dataToFill = [
            'commentable_id' => $item->getKey(),
            'commentable_type' => $item->getMorphClass(),
        ];

        $components = $this->prepareComponents($resource->getFormPage()->fields(), $formId, $dataToFill);

        return [
            'isPersisted' => true,
            'comments' => $this->getCardBuilder(),
            'form' => FormBuilder::make($resource->getRoute('crud.store'))
                ->name($formId)
                ->withoutRedirect()
                ->async(events: [
                    AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'crud-list'),
                    AlpineJs::event(JsEvent::FORM_RESET, $formId),
                ])
                ->customAttributes([
                    'id' => $formId,
                ])
                ->class('hidden')
                ->hideSubmit(),
            'components' => Components::make($components),
        ];
    }
}
