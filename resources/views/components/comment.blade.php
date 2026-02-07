@props([
    'commenter',
    'avatar',
    'message',
    'createdAt' => null,
    'updatedAt' => null,
    'isAuthor' => false,
])

<div class="comment-card {{ $isAuthor ? 'is-author' : 'is-other' }}">
    @if(!$isAuthor)
        <div class="comment-avatar">
            <x-moonshine::img :src="$avatar" />
        </div>
    @endif

    <div class="comment-bubble {{ $isAuthor ? 'bgc-info' : 'bgc-gray' }}">
        <div class="text-sm font-bold">{{ $commenter }}</div>
        <div class="text-xs">
            {!! str($message)->markdown(['renderer' => ['soft_break' => "<br>\n"]]) !!}
        </div>
        <div class="comment-meta">
            <small class="comment-time">{{ $createdAt }}</small>
        </div>
    </div>
</div>
