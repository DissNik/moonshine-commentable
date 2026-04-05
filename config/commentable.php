<?php

return [
    'models' => [
        'comment' => DissNik\MoonShineCommentable\Models\Comment::class,
        'comment_read' => DissNik\MoonShineCommentable\Models\CommentRead::class,
    ],
    'policies' => [
        'comment' => DissNik\MoonShineCommentable\Policies\CommentPolicy::class,
    ],
    'moonshine' => [
        'register_resource' => true,
        'resource' => DissNik\MoonShineCommentable\Resources\CommentResource::class,
        'pages' => [
            'index' => DissNik\MoonShineCommentable\Resources\Pages\CommentIndexPage::class,
            'form' => DissNik\MoonShineCommentable\Resources\Pages\CommentFormPage::class,
        ],
        'events' => [
            'comment_added' => 'moonshine-commentable:comment-added',
        ],
    ],
    'ui' => [
        'height' => '600px',
        'threshold' => 200,
    ],
    'transport' => [
        'mode' => 'polling',
        'polling' => [
            'interval' => null,
        ],
    ],
];
