<div @class(['commentable-field', 'commentable-field--fill' => $fillAvailableSpace ?? false])>
    @if($isPersisted)
        <x-moonshine::layout.grid class="commentable-field__layout">
            <x-moonshine::layout.column class="commentable-field__messages">
                {!! $comments !!}
            </x-moonshine::layout.column>

            @if(!($fillAvailableSpace ?? false) || $formComponents->hasComponents())
                <x-moonshine::layout.column class="commentable-field__composer">
                    {!! $formComponents !!}
                </x-moonshine::layout.column>
            @endif
        </x-moonshine::layout.grid>

        @if($form)
            <template x-teleport="body">
                {!! $form !!}
            </template>
        @endif
    @else
        <x-moonshine::alert type="default">
            {{ __('moonshine-commentable::ui.available_after_save') }}
        </x-moonshine::alert>
    @endif
</div>
