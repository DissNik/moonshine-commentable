<?php

namespace DissNik\MoonShineCommentable\Resources\Pages;

use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Fieldset;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\ID;

class CommentFormPage extends FormPage
{
    public function fields(): array
    {
        return [
            ID::make(),

            Hidden::make('commentable_id'),
            Hidden::make('commentable_type'),
            Hidden::make('author_id'),
            Hidden::make('author_type'),

            Fieldset::make(__('moonshine-commentable::ui.message'), [
                Flex::make([
                    Text::make('', 'text')
                        ->required(),
                    ActionButton::make(__('moonshine-commentable::ui.send'))
                        ->secondary()
                        ->dispatchEvent(AlpineJs::event(JsEvent::FORM_SUBMIT, 'comment_form'))
                ])
                    ->unwrap()
                    ->itemsAlign('end')
            ]),
        ];
    }

    public function rules(DataWrapperContract $item): array
    {
        return [
            'text' => ['required', 'min:3'],
        ];
    }
}
