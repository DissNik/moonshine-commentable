<?php

return [
    'comment' => [
        'model' => DissNik\MoonShineCommentable\Models\Comment::class,
        'policy' => DissNik\MoonShineCommentable\Policies\CommentPolicy::class,
    ],
    'comment_read' => [
        'model' => DissNik\MoonShineCommentable\Models\CommentRead::class,
    ],
    'height' => '600px',
    'threshold' => 200,
    'interval' => null
];
