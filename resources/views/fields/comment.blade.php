<div>
    @if($isPersisted)
        <x-moonshine::layout.grid class="w-full">
            <x-moonshine::layout.column>
                <div
                    class="components"
                    x-data="{
                        scrollToBottom() {
                            const grid = $el.querySelector('.grid.grid-cols-12.gap-6');
                            if (grid) {
                                grid.scrollTop = grid.scrollHeight;
                                setTimeout(() => { grid.scrollTop = grid.scrollHeight; }, 50);
                            }
                        },
                        initObserver() {
                            const observer = new MutationObserver(() => this.scrollToBottom());
                            observer.observe($el, { childList: true, subtree: true });
                        }
                    }"
                    x-init="scrollToBottom(); initObserver();"
                    @fragment_updated:crud-list.window="scrollToBottom()"
                >
                    {!! $comments !!}
                </div>
            </x-moonshine::layout.column>

            <x-moonshine::layout.column>
                {!! $components !!}
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

<style>
    .components .grid.grid-cols-12.gap-6 {
        max-height: {{ config('moonshine-commentable.height', '600px') }};
        overflow-y: auto;
        scroll-behavior: smooth;
        gap: calc(var(--spacing) * 2);
    }
</style>
