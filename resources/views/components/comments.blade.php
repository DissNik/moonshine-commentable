@props([
    'comments',
    'bulkButtons' => [],
    'asyncUrl' => '',
    'async' => false,
    'notfound' => false,
    'colSpan' => 12,
    'adaptiveColSpan' => 12,
    'name' => 'default',
    'translates' => [],
    'searchable' => false,
    'searchValue' => '',
    'topLeft' => null,
    'topRight' => null,
])
<div class="js-cards-builder-container">
    <div x-data="cardsBuilder(
    {{ (int) $async }},
    '{{ $asyncUrl }}'
)"
        @defineEventWhen($async, 'cards_updated', $name, 'asyncRequest')
        {{ $attributes }}
    >
        <x-moonshine::iterable-wrapper
            :searchable="$async && $searchable"
            :search-placeholder="$translates['search']"
            :search-value="$searchValue"
            :search-url="$asyncUrl"
        >
            <x-slot:topLeft>
                {!! $topLeft ?? '' !!}
            </x-slot:topLeft>

            <x-slot:topRight>
                {!! $topRight ?? '' !!}
            </x-slot:topRight>

            @if($comments->hasComponents())
                {!! $comments !!}

                @if($hasPaginator)
                    {!! $paginator !!}
                @endif
            @else
                <x-moonshine::alert type="default" class="my-4" icon="s.no-symbol">
                    {{ $translates['notfound'] }}
                </x-moonshine::alert>
            @endif
        </x-moonshine::iterable-wrapper>
    </div>
</div>

@once
    @push('scripts')
        <script>
            if (!window.commentableCommentsList) {
                window.commentableCommentsList = function commentableCommentsList(config) {
                    return {
                        autoRefreshEnabled: true,
                        initialOpenScrollPending: false,
                        pendingForceScroll: false,
                        skipInitialMutation: true,
                        lastItemsSignature: '',
                        mutationObserver: null,

                        init() {
                            this.$el.dataset.commentsAutorefresh = '1';
                            this.lastItemsSignature = this.getItemsSignature();
                            this.observeMutations();
                            this.syncScrollState();
                            requestAnimationFrame(() => this.syncScrollState());
                            this.$el.addEventListener('scroll', () => this.syncScrollState(), { passive: true });
                        },

                        getItemsContainer() {
                            return this.$el.querySelector(`[data-comments-items="${config.listName}"]`);
                        },

                        getLastCommentElement() {
                            return this.getItemsContainer()?.lastElementChild ?? null;
                        },

                        getItemsSignature() {
                            const items = Array.from(this.getItemsContainer()?.querySelectorAll('[data-comment-item]') ?? []);

                            return items.map((item) => item.textContent?.trim() ?? '').join('|');
                        },

                        isAtBottom() {
                            return (this.$el.scrollHeight - this.$el.scrollTop - this.$el.clientHeight) < config.threshold;
                        },

                        applyScrollToBottom(force = false) {
                            if (!force && !this.autoRefreshEnabled && !this.pendingForceScroll) {
                                return;
                            }

                            const lastComment = this.getLastCommentElement();

                            if (lastComment) {
                                const targetTop = lastComment.offsetTop + lastComment.offsetHeight - this.$el.clientHeight;
                                this.$el.scrollTop = Math.max(0, targetTop);
                            } else {
                                this.$el.scrollTop = this.$el.scrollHeight;
                            }

                            this.pendingForceScroll = false;
                            this.autoRefreshEnabled = true;
                            this.syncScrollState();
                        },

                        scheduleScrollToBottom(force = false) {
                            if (!force && !this.autoRefreshEnabled && !this.pendingForceScroll) {
                                return;
                            }

                            setTimeout(() => this.applyScrollToBottom(force), 40);
                            requestAnimationFrame(() => requestAnimationFrame(() => this.applyScrollToBottom(force)));
                            setTimeout(() => this.applyScrollToBottom(force), 140);
                        },

                        queueScroll(force = false) {
                            this.pendingForceScroll = this.pendingForceScroll || force;
                        },

                        observeMutations() {
                            this.mutationObserver?.disconnect();

                            this.mutationObserver = new MutationObserver((mutations) => {
                                const hasStructuralChange = mutations.some((mutation) =>
                                    mutation.type === 'childList' && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0)
                                );

                                if (!hasStructuralChange) {
                                    return;
                                }

                                const nextSignature = this.getItemsSignature();

                                if (!nextSignature || nextSignature === this.lastItemsSignature) {
                                    return;
                                }

                                this.lastItemsSignature = nextSignature;

                                if (this.skipInitialMutation && !this.pendingForceScroll && !this.initialOpenScrollPending) {
                                    this.skipInitialMutation = false;
                                    this.syncScrollState();

                                    return;
                                }

                                this.skipInitialMutation = false;
                                this.initialOpenScrollPending = false;
                                this.scheduleScrollToBottom(this.pendingForceScroll);
                            });

                            this.mutationObserver.observe(this.$el, {
                                childList: true,
                                subtree: true,
                            });
                        },

                        syncScrollState() {
                            this.autoRefreshEnabled = this.isAtBottom();
                            this.$el.dataset.commentsAutorefresh = this.autoRefreshEnabled ? '1' : '0';
                        },
                    };
                }
            }
        </script>
    @endpush
@endonce
