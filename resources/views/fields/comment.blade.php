<div>
    @if($isPersisted)
        <x-moonshine::layout.grid>
            <x-moonshine::layout.column>
                {!! $comments !!}
            </x-moonshine::layout.column>

            <x-moonshine::layout.column>
                {!! $formComponents !!}
            </x-moonshine::layout.column>
        </x-moonshine::layout.grid>

        <template x-teleport="body">
            {!! $form !!}
        </template>
    @else
        <x-moonshine::alert type="default">
            {{ __('moonshine-commentable::ui.available_after_save') }}
        </x-moonshine::alert>
    @endif
</div>
