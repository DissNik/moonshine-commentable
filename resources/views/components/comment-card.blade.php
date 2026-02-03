@props([
    'item',
    'author' => $item->author,
    'isAuthor' => false,
])

<div class="comment-card {{ $isAuthor ? 'is-author' : 'is-other' }}">
    @if(!$isAuthor)
        <div class="comment-avatar shrink-0">
            <x-moonshine::img
                :src="$author->getCommenterAvatar()"
                width="35"
                height="35"
                class="rounded-full"
            />
        </div>
    @endif

    <div class="comment-bubble {{ $isAuthor ? 'bgc-info' : 'bgc-gray' }} p-2 px-3 w-full">
        <div class="text-sm font-bold">{{ $author->getCommenterName() }}</div>
        <div class="text-xs">{{ $item->text }}</div>
        <div class="comment-meta">
            <div class="comment-time"><small>{{ $item->created_at->translatedFormat('d M H:i') }}</small></div>
        </div>
    </div>
</div>
