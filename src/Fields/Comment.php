<?php

namespace DissNik\MoonShineCommentable\Fields;

use Closure;
use DissNik\MoonShineCommentable\Resources\Pages\CommentIndexPage;
use DissNik\MoonShineCommentable\Support\CommentableConfig;
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
            $resource = CommentableConfig::moonShineResource();
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

        if (! is_null($casted) && method_exists($resource, 'setQueryParams') && method_exists($resource, 'getQueryParamName')) {
            $resource->setQueryParams([
                $resource->getQueryParamName('commentable_id') => $casted->getKey(),
                $resource->getQueryParamName('commentable_type') => $casted->getMorphClass(),
            ]);
        }

        $component = $resource
            ->customQueryBuilder($relation)
            ->getIndexPage()
            ->getListComponent(true);

        if (! is_null($casted)) {
            if (method_exists($component, 'nowOnParams')) {
                $component->nowOnParams([
                    'commentable_id' => $casted->getKey(),
                    'commentable_type' => $casted->getMorphClass(),
                ]);
            }

            if (method_exists($component, 'commentable')) {
                $component->commentable(
                    $casted->getKey(),
                    $casted->getMorphClass(),
                );
            }
        }

        return $component;
    }

    protected function prepareFormComponents(iterable $components, string $formId, $data): Collection
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

                    $this->prepareFormComponents($children, $formId, $data);
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
        $formComponents = $this->prepareFormComponents($resource->getFormPage()->fields(), $formId, $dataToFill);

        return [
            'isPersisted' => true,
            'comments' => $this->getCardBuilder(),
            'form' => FormBuilder::make($resource->getRoute('crud.store'))
                ->name($formId)
                ->withoutRedirect()
                ->async(events: [
                    AlpineJs::event(JsEvent::FRAGMENT_UPDATED, CommentIndexPage::LIST_COMPONENT_NAME, [
                        'commentable_id' => (string) $item->getKey(),
                        'commentable_type' => (string) $item->getMorphClass(),
                    ]),
                    AlpineJs::event(JsEvent::FORM_RESET, $formId),
                    AlpineJs::event(CommentableConfig::commentAddedEvent()),
                ])
                ->customAttributes([
                    'id' => $formId,
                ])
                ->class('hidden')
                ->hideSubmit(),
            'formComponents' => Components::make($formComponents),
        ];
    }
}
